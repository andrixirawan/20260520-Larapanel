<?php

namespace App\Services\Pos;

use App\Models\Pos\Payment;
use App\Models\Pos\Shift;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosShiftService
{
    public function __construct(
        private readonly PosAuditLogger $auditLogger,
    ) {}

    public function open(User $actor, float|int|string $openingCash, ?string $notes = null): Shift
    {
        return DB::transaction(function () use ($actor, $openingCash, $notes): Shift {
            $hasOpenShift = Shift::query()
                ->where('cashier_id', $actor->id)
                ->where('status', Shift::STATUS_OPEN)
                ->exists();

            if ($hasOpenShift) {
                throw ValidationException::withMessages([
                    'shift' => __('You already have an open shift.'),
                ]);
            }

            $shift = Shift::create([
                'cashier_id' => $actor->id,
                'opened_by' => $actor->id,
                'status' => Shift::STATUS_OPEN,
                'opening_cash' => $this->money($openingCash),
                'opened_at' => now(),
                'notes' => $notes,
            ]);

            $this->auditLogger->log($actor, 'pos.shift.opened', $shift, null, [
                'opening_cash' => $shift->opening_cash,
            ]);

            return $shift;
        });
    }

    public function close(User $actor, Shift $shift, float|int|string $countedCash, ?string $notes = null): Shift
    {
        return DB::transaction(function () use ($actor, $shift, $countedCash, $notes): Shift {
            $lockedShift = Shift::query()
                ->whereKey($shift->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedShift->status !== Shift::STATUS_OPEN) {
                throw ValidationException::withMessages([
                    'shift' => __('This shift is already closed.'),
                ]);
            }

            $cashSales = Payment::query()
                ->where('method', Payment::METHOD_CASH)
                ->where('status', Payment::STATUS_PAID)
                ->whereHas('sale', fn ($query) => $query->where('shift_id', $lockedShift->id))
                ->sum('amount');

            $expectedCash = round((float) $lockedShift->opening_cash + (float) $cashSales, 2);
            $counted = round((float) $countedCash, 2);
            $difference = round($counted - $expectedCash, 2);

            $before = $lockedShift->toArray();

            $lockedShift->update([
                'closed_by' => $actor->id,
                'status' => Shift::STATUS_CLOSED,
                'expected_cash' => $this->money($expectedCash),
                'counted_cash' => $this->money($counted),
                'cash_difference' => $this->money($difference),
                'closed_at' => now(),
                'notes' => $notes ?? $lockedShift->notes,
            ]);

            $this->auditLogger->log($actor, 'pos.shift.closed', $lockedShift, $before, [
                'expected_cash' => $lockedShift->expected_cash,
                'counted_cash' => $lockedShift->counted_cash,
                'cash_difference' => $lockedShift->cash_difference,
            ]);

            return $lockedShift->refresh();
        });
    }

    private function money(float|int|string $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
