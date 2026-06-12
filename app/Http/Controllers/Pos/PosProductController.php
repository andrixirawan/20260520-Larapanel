<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Pos\Product;
use App\Models\Pos\ProductVariant;
use App\Services\Pos\PosInventoryService;
use App\Support\TableQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PosProductController extends Controller
{
    public function __construct(
        private readonly PosInventoryService $inventoryService,
    ) {}

    public function index(Request $request): Response
    {
        $search = TableQuery::search($request);
        $sortOptions = [
            'name' => 'name',
            'sku' => 'sku',
            'status' => 'status',
            'created_at' => 'created_at',
        ];
        $sort = TableQuery::sort($request, $sortOptions, 'created_at');
        $direction = TableQuery::direction($request);
        $perPage = TableQuery::perPage($request);

        $products = Product::query()
            ->with(['defaultVariant.stock'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhereHas('variants', fn ($variantQuery) => $variantQuery
                            ->where('sku', 'like', "%{$search}%")
                            ->orWhere('barcode', 'like', "%{$search}%"));
                });
            })
            ->orderBy($sortOptions[$sort], $direction)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (Product $product): array {
                $variant = $product->defaultVariant;
                $hasSales = $product->saleItems()->exists()
                    || ($variant?->saleItems()->exists() ?? false);

                return [
                    'public_id' => $product->public_id,
                    'name' => $product->name,
                    'sku' => $variant?->sku ?? $product->sku,
                    'status' => $product->status,
                    'product_variant_public_id' => $variant?->public_id,
                    'price' => (float) ($variant?->price ?? 0),
                    'cost_price' => $variant?->cost_price ? (float) $variant->cost_price : null,
                    'track_inventory' => (bool) $variant?->track_inventory,
                    'allow_backorder' => (bool) $variant?->allow_backorder,
                    'low_stock_threshold' => (float) ($variant?->low_stock_threshold ?? 0),
                    'stock' => (float) ($variant?->stock?->quantity_on_hand ?? 0),
                    'is_low_stock' => (bool) $variant?->track_inventory
                        && (float) ($variant?->low_stock_threshold ?? 0) > 0
                        && (float) ($variant?->stock?->quantity_on_hand ?? 0) <= (float) ($variant?->low_stock_threshold ?? 0),
                    'description' => $product->description,
                    'has_sales' => $hasSales,
                    'created_at' => $product->created_at?->toISOString(),
                ];
            });

        return Inertia::render('pos/products', [
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
            'products' => $products,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('pos_products', 'sku'),
                Rule::unique('pos_product_variants', 'sku'),
            ],
            'barcode' => ['nullable', 'string', 'max:255', Rule::unique('pos_product_variants', 'barcode')],
            'price' => ['required', 'numeric', 'min:0.01'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'initial_quantity' => ['nullable', 'numeric', 'min:0'],
            'track_inventory' => ['nullable', 'boolean'],
            'allow_backorder' => ['nullable', 'boolean'],
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        $this->inventoryService->createProduct($request->user(), $validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product created.')]);

        return back();
    }

    public function adjustStock(Request $request, ProductVariant $variant): RedirectResponse
    {
        $validated = $request->validate([
            'quantity_delta' => ['required', 'numeric', 'not_in:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->inventoryService->adjustStock(
            $request->user(),
            $variant->load('product'),
            $validated['quantity_delta'],
            $validated['notes'] ?? null,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Stock adjusted.')]);

        return back();
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $defaultVariant = $product->defaultVariant()->first();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('pos_products', 'sku')->ignore($product->id),
                Rule::unique('pos_product_variants', 'sku')->ignore($defaultVariant?->id),
            ],
            'price' => ['required', 'numeric', 'min:0.01'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'track_inventory' => ['nullable', 'boolean'],
            'allow_backorder' => ['nullable', 'boolean'],
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in([Product::STATUS_ACTIVE, Product::STATUS_INACTIVE])],
        ]);

        $this->inventoryService->updateProduct($request->user(), $product, $validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product updated.')]);

        return back();
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->inventoryService->deleteProduct($request->user(), $product);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product deleted.')]);

        return back();
    }

    public function stockOpname(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_public_id' => ['required', 'string', 'exists:pos_product_variants,public_id'],
            'items.*.counted_quantity' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $variantIds = ProductVariant::query()
            ->whereIn('public_id', collect($validated['items'])->pluck('product_variant_public_id')->all())
            ->pluck('id', 'public_id');

        $result = $this->inventoryService->batchStockOpname(
            $request->user(),
            collect($validated['items'])
                ->map(fn (array $item): array => [
                    'product_variant_id' => $variantIds[$item['product_variant_public_id']],
                    'counted_quantity' => $item['counted_quantity'],
                ])
                ->all(),
            $validated['notes'] ?? null,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Stock opname saved. Adjusted: :adjusted, unchanged: :unchanged.', $result),
        ]);

        return back();
    }
}
