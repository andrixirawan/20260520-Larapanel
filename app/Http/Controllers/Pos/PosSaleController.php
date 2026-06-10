<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Pos\Payment;
use App\Models\Pos\Sale;
use App\Services\Pos\PosSaleService;
use App\Support\AccessControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PosSaleController extends Controller
{
    public function __construct(
        private readonly PosSaleService $saleService,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $search = trim($request->string('search')->toString());

        $sales = Sale::query()
            ->with(['cashier:id,name', 'payments:id,sale_id,method,amount'])
            ->when(! $user->can(AccessControl::PERMISSION_POS_SHIFTS_MANAGE), fn ($query) => $query->where('cashier_id', $user->id))
            ->when($search !== '', fn ($query) => $query->where('invoice_number', 'like', "%{$search}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Sale $sale): array => [
                'id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'cashier' => $sale->cashier?->name,
                'status' => $sale->status,
                'payment_status' => $sale->payment_status,
                'payment_method' => $sale->payments->first()?->method,
                'total' => (float) $sale->total,
                'created_at' => $sale->created_at?->toISOString(),
            ]);

        return Inertia::render('pos/sales', [
            'filters' => ['search' => $search],
            'sales' => $sales,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'integer', 'exists:pos_product_variants,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'payment_method' => ['required', 'string', Rule::in(array_keys(Payment::methodOptions()))],
            'received_amount' => ['nullable', 'numeric', 'min:0'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $sale = $this->saleService->create($request->user(), $validated);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Sale :invoice completed.', ['invoice' => $sale->invoice_number]),
        ]);

        return back();
    }

    public function show(Request $request, Sale $sale): Response
    {
        abort_unless(
            $sale->cashier_id === $request->user()->id || $request->user()->can(AccessControl::PERMISSION_POS_SHIFTS_MANAGE),
            403,
        );

        return Inertia::render('pos/sale-show', [
            'sale' => $sale->load(['cashier:id,name', 'shift:id,opened_at', 'items', 'payments']),
        ]);
    }
}
