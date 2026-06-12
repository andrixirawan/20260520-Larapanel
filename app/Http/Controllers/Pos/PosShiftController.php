<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Pos\FinanceEntry;
use App\Models\Pos\Shift;
use App\Models\User;
use App\Services\Pos\PosShiftService;
use App\Support\AccessControl;
use App\Support\TableQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
        $search = TableQuery::search($request);
        $sortOptions = [
            'id' => 'id',
            'status' => 'status',
            'opening_cash' => 'opening_cash',
            'cash_difference' => 'cash_difference',
            'opened_at' => 'opened_at',
        ];
        $sort = TableQuery::sort($request, $sortOptions, 'opened_at');
        $direction = TableQuery::direction($request);
        $perPage = TableQuery::perPage($request, 15);

        $shifts = Shift::query()
            ->with([
                'cashier:id,name',
                'openedBy:id,name',
                'closedBy:id,name',
                'handoverToCashier:id,name,public_id',
                'handoverRequestedBy:id,name',
                'handoverApprovedBy:id,name',
            ])
            ->when(! $user->can(AccessControl::PERMISSION_POS_SHIFTS_MANAGE), fn ($query) => $query->where('cashier_id', $user->id))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder
                        ->where('status', 'like', "%{$search}%")
                        ->orWhereHas('cashier', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('openedBy', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('closedBy', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"))
                        ->orWhere('public_id', 'like', "%{$search}%");

                    if (is_numeric($search)) {
                        $builder->orWhere('id', (int) $search);
                    }
                });
            })
            ->orderBy($sortOptions[$sort], $direction)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (Shift $shift): array {
                $snapshot = $shift->status === Shift::STATUS_OPEN
                    ? $this->shiftService->cashSnapshot($shift)
                    : null;

                return [
                    'public_id' => $shift->public_id,
                    'cashier' => $shift->cashier?->name,
                    'opened_by' => $shift->openedBy?->name,
                    'closed_by' => $shift->closedBy?->name,
                    'handover_to_cashier' => $shift->handoverToCashier?->name,
                    'handover_requested_by' => $shift->handoverRequestedBy?->name,
                    'handover_approved_by' => $shift->handoverApprovedBy?->name,
                    'status' => $shift->status,
                    'opening_cash' => (float) $shift->opening_cash,
                    'expected_cash' => $snapshot['expected_cash'] ?? ($shift->expected_cash ? (float) $shift->expected_cash : null),
                    'counted_cash' => $shift->counted_cash ? (float) $shift->counted_cash : null,
                    'cash_difference' => $shift->cash_difference ? (float) $shift->cash_difference : null,
                    'opened_at' => $shift->opened_at?->toISOString(),
                    'closed_at' => $shift->closed_at?->toISOString(),
                    'handover_requested_at' => $shift->handover_requested_at?->toISOString(),
                    'handover_approved_at' => $shift->handover_approved_at?->toISOString(),
                    'handover_notes' => $shift->handover_notes,
                    'requires_handover_approval' => $shift->status === Shift::STATUS_HANDOVER_PENDING,
                ];
            });

        return Inertia::render('pos/shifts', [
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
            'shifts' => $shifts,
            'handoverDifferenceThreshold' => $this->shiftService->handoverDifferenceThreshold(),
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

    public function storeCashMovement(Request $request, Shift $shift): RedirectResponse
    {
        abort_unless(
            $shift->cashier_id === $request->user()->id || $request->user()->can(AccessControl::PERMISSION_POS_SHIFTS_MANAGE),
            403,
        );

        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in([FinanceEntry::TYPE_CASH_IN, FinanceEntry::TYPE_CASH_OUT])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['required', 'string', 'max:500'],
        ]);

        $this->shiftService->recordCashMovement(
            $request->user(),
            $shift,
            $validated['type'],
            $validated['amount'],
            $validated['notes'],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Drawer cash movement recorded.')]);

        return back();
    }

    public function handover(Request $request, Shift $shift): RedirectResponse
    {
        abort_unless(
            $shift->cashier_id === $request->user()->id || $request->user()->can(AccessControl::PERMISSION_POS_SHIFTS_MANAGE),
            403,
        );

        $validated = $request->validate([
            'incoming_cashier_public_id' => ['required', 'string', 'exists:users,public_id'],
            'counted_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $incomingCashier = User::query()
            ->where('public_id', $validated['incoming_cashier_public_id'])
            ->firstOrFail();

        $result = $this->shiftService->handover(
            $request->user(),
            $shift,
            $incomingCashier,
            $validated['counted_cash'],
            $validated['notes'] ?? null,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $result['requires_approval']
                ? __('Handover submitted. Admin approval is required before the next shift opens.')
                : __('Handover completed and the next shift is now open.'),
        ]);

        return back();
    }

    public function approveHandover(Request $request, Shift $shift): RedirectResponse
    {
        abort_unless($request->user()->can(AccessControl::PERMISSION_POS_SHIFTS_MANAGE), 403);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->shiftService->approveHandover(
            $request->user(),
            $shift,
            $validated['notes'] ?? null,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Handover approved and replacement shift opened.'),
        ]);

        return back();
    }
}
