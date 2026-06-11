<?php

namespace App\Services\Pos;

use App\Models\Pos\FinanceEntry;
use App\Models\Pos\Payment;
use App\Models\Pos\Shift;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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

            $snapshot = $this->cashSnapshot($lockedShift);
            $expectedCash = $snapshot['expected_cash'];
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

    public function recordCashMovement(
        User $actor,
        Shift $shift,
        string $type,
        float|int|string $amount,
        string $notes,
    ): FinanceEntry {
        $movementType = (string) $type;
        $movementAmount = round((float) $amount, 2);
        $movementNotes = trim($notes);

        validator(
            [
                'type' => $movementType,
                'amount' => $movementAmount,
                'notes' => $movementNotes,
            ],
            [
                'type' => ['required', Rule::in([FinanceEntry::TYPE_CASH_IN, FinanceEntry::TYPE_CASH_OUT])],
                'amount' => ['required', 'numeric', 'min:0.01'],
                'notes' => ['required', 'string', 'max:500'],
            ],
        )->validate();

        return DB::transaction(function () use ($actor, $shift, $movementType, $movementAmount, $movementNotes): FinanceEntry {
            $lockedShift = Shift::query()
                ->whereKey($shift->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedShift->status !== Shift::STATUS_OPEN) {
                throw ValidationException::withMessages([
                    'shift' => __('This shift is already closed.'),
                ]);
            }

            $entry = FinanceEntry::create([
                'entry_date' => now()->toDateString(),
                'shift_id' => $lockedShift->id,
                'type' => $movementType,
                'direction' => $movementType === FinanceEntry::TYPE_CASH_IN
                    ? FinanceEntry::DIRECTION_CREDIT
                    : FinanceEntry::DIRECTION_DEBIT,
                'payment_method' => Payment::METHOD_CASH,
                'amount' => $this->money($movementAmount),
                'created_by' => $actor->id,
                'notes' => $movementNotes,
                'metadata' => [
                    'source' => 'drawer_movement',
                ],
            ]);

            $this->auditLogger->log($actor, 'pos.shift.cash_movement.recorded', $entry, null, [
                'shift_id' => $lockedShift->id,
                'type' => $movementType,
                'amount' => $entry->amount,
                'notes' => $movementNotes,
            ]);

            return $entry;
        });
    }

    /**
     * @return array{opening_cash: float, cash_sales_total: float, drawer_cash_in_total: float, drawer_cash_out_total: float, net_cash_movement_total: float, expected_cash: float}
     */
    public function cashSnapshot(Shift $shift): array
    {
        $cashSales = Payment::query()
            ->where('method', Payment::METHOD_CASH)
            ->where('status', Payment::STATUS_PAID)
            ->whereHas('sale', fn ($query) => $query->where('shift_id', $shift->id))
            ->sum('amount');

        $cashIn = FinanceEntry::query()
            ->where('shift_id', $shift->id)
            ->where('type', FinanceEntry::TYPE_CASH_IN)
            ->sum('amount');

        $cashOut = FinanceEntry::query()
            ->where('shift_id', $shift->id)
            ->where('type', FinanceEntry::TYPE_CASH_OUT)
            ->sum('amount');

        $openingCash = round((float) $shift->opening_cash, 2);
        $cashSalesTotal = round((float) $cashSales, 2);
        $drawerCashInTotal = round((float) $cashIn, 2);
        $drawerCashOutTotal = round((float) $cashOut, 2);
        $netCashMovementTotal = round($drawerCashInTotal - $drawerCashOutTotal, 2);

        return [
            'opening_cash' => $openingCash,
            'cash_sales_total' => $cashSalesTotal,
            'drawer_cash_in_total' => $drawerCashInTotal,
            'drawer_cash_out_total' => $drawerCashOutTotal,
            'net_cash_movement_total' => $netCashMovementTotal,
            'expected_cash' => round($openingCash + $cashSalesTotal + $netCashMovementTotal, 2),
        ];
    }

    /**
     * @return array{recommended_opening_cash: float, source_shift_public_id: string, source_closed_at: string|null}|null
     */
    public function openingGuide(User $actor): ?array
    {
        $lastClosedShift = Shift::query()
            ->where('cashier_id', $actor->id)
            ->where('status', Shift::STATUS_CLOSED)
            ->whereNotNull('counted_cash')
            ->latest('closed_at')
            ->first();

        if (! $lastClosedShift) {
            return null;
        }

        return [
            'recommended_opening_cash' => round((float) $lastClosedShift->counted_cash, 2),
            'source_shift_public_id' => $lastClosedShift->public_id,
            'source_closed_at' => $lastClosedShift->closed_at?->toISOString(),
        ];
    }

    private function money(float|int|string $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
