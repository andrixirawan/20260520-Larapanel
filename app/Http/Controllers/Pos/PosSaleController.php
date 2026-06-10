<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Pos\Payment;
use App\Models\Pos\ProductVariant;
use App\Models\Pos\Sale;
use App\Services\Pos\PosSaleService;
use App\Support\AccessControl;
use App\Support\TableQuery;
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
        $search = TableQuery::search($request);
        $sortOptions = [
            'invoice_number' => 'invoice_number',
            'status' => 'status',
            'payment_status' => 'payment_status',
            'total' => 'total',
            'created_at' => 'created_at',
        ];
        $sort = TableQuery::sort($request, $sortOptions, 'created_at');
        $direction = TableQuery::direction($request);
        $perPage = TableQuery::perPage($request);

        $sales = Sale::query()
            ->with(['cashier:id,name', 'payments:id,sale_id,method,amount'])
            ->when(! $user->can(AccessControl::PERMISSION_POS_SHIFTS_MANAGE), fn ($query) => $query->where('cashier_id', $user->id))
            ->when($search !== '', fn ($query) => $query->where('invoice_number', 'like', "%{$search}%"))
            ->orderBy($sortOptions[$sort], $direction)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Sale $sale): array => [
                'public_id' => $sale->public_id,
                'invoice_number' => $sale->invoice_number,
                'cashier' => $sale->cashier?->name,
                'status' => $sale->status,
                'payment_status' => $sale->payment_status,
                'payment_method' => $sale->payments->first()?->method,
                'total' => (float) $sale->total,
                'created_at' => $sale->created_at?->toISOString(),
            ]);

        return Inertia::render('pos/sales', [
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
            'sales' => $sales,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_public_id' => ['required', 'string', 'exists:pos_product_variants,public_id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'payment_method' => ['required', 'string', Rule::in(array_keys(Payment::methodOptions()))],
            'received_amount' => ['nullable', 'numeric', 'min:0'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['items'] = $this->itemsWithInternalVariantIds($validated['items']);

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
            'sale' => $this->saleData($sale->load(['cashier:id,name', 'shift:id,public_id,opened_at', 'items', 'payments'])),
        ]);
    }

    /**
     * @param  array<int, array{product_variant_public_id: string, quantity: mixed}>  $items
     * @return array<int, array{product_variant_id: int, quantity: mixed}>
     */
    private function itemsWithInternalVariantIds(array $items): array
    {
        $variants = ProductVariant::query()
            ->whereIn('public_id', collect($items)->pluck('product_variant_public_id')->all())
            ->pluck('id', 'public_id');

        return collect($items)
            ->map(fn (array $item): array => [
                'product_variant_id' => $variants[$item['product_variant_public_id']],
                'quantity' => $item['quantity'],
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function saleData(Sale $sale): array
    {
        return [
            'public_id' => $sale->public_id,
            'invoice_number' => $sale->invoice_number,
            'cashier' => $sale->cashier ? [
                'name' => $sale->cashier->name,
            ] : null,
            'shift' => $sale->shift ? [
                'public_id' => $sale->shift->public_id,
                'opened_at' => $sale->shift->opened_at?->toISOString(),
            ] : null,
            'status' => $sale->status,
            'payment_status' => $sale->payment_status,
            'subtotal' => (float) $sale->subtotal,
            'discount_total' => (float) $sale->discount_total,
            'tax_total' => (float) $sale->tax_total,
            'total' => (float) $sale->total,
            'paid_total' => (float) $sale->paid_total,
            'change_total' => (float) $sale->change_total,
            'customer_name' => $sale->customer_name,
            'notes' => $sale->notes,
            'completed_at' => $sale->completed_at?->toISOString(),
            'created_at' => $sale->created_at?->toISOString(),
            'items' => $sale->items->map(fn ($item): array => [
                'public_id' => $item->public_id,
                'sku_snapshot' => $item->sku_snapshot,
                'name_snapshot' => $item->name_snapshot,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount_total' => (float) $item->discount_total,
                'tax_total' => (float) $item->tax_total,
                'line_total' => (float) $item->line_total,
            ])->values(),
            'payments' => $sale->payments->map(fn (Payment $payment): array => [
                'public_id' => $payment->public_id,
                'method' => $payment->method,
                'status' => $payment->status,
                'amount' => (float) $payment->amount,
                'received_amount' => $payment->received_amount ? (float) $payment->received_amount : null,
                'change_amount' => (float) $payment->change_amount,
                'provider' => $payment->provider,
                'provider_reference' => $payment->provider_reference,
            ])->values(),
        ];
    }
}
