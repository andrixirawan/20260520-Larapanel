<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Pos\Shift;
use App\Services\Pos\PosShiftService;
use App\Support\AccessControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosShiftController extends Controller
{
    public function __construct(
        private readonly PosShiftService $shiftService,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $shifts = Shift::query()
            ->with(['cashier:id,name', 'openedBy:id,name', 'closedBy:id,name'])
            ->when(! $user->can(AccessControl::PERMISSION_POS_SHIFTS_MANAGE), fn ($query) => $query->where('cashier_id', $user->id))
            ->latest('opened_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Shift $shift): array => [
                'id' => $shift->id,
                'cashier' => $shift->cashier?->name,
                'opened_by' => $shift->openedBy?->name,
                'closed_by' => $shift->closedBy?->name,
                'status' => $shift->status,
                'opening_cash' => (float) $shift->opening_cash,
                'expected_cash' => $shift->expected_cash ? (float) $shift->expected_cash : null,
                'counted_cash' => $shift->counted_cash ? (float) $shift->counted_cash : null,
                'cash_difference' => $shift->cash_difference ? (float) $shift->cash_difference : null,
                'opened_at' => $shift->opened_at?->toISOString(),
                'closed_at' => $shift->closed_at?->toISOString(),
            ]);

        return Inertia::render('pos/shifts', [
            'shifts' => $shifts,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'opening_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->shiftService->open(
            $request->user(),
            $validated['opening_cash'],
            $validated['notes'] ?? null,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Shift opened.')]);

        return back();
    }

    public function close(Request $request, Shift $shift): RedirectResponse
    {
        abort_unless(
            $shift->cashier_id === $request->user()->id || $request->user()->can(AccessControl::PERMISSION_POS_SHIFTS_MANAGE),
            403,
        );

        $validated = $request->validate([
            'counted_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->shiftService->close(
            $request->user(),
            $shift,
            $validated['counted_cash'],
            $validated['notes'] ?? null,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Shift closed.')]);

        return back();
    }
}
