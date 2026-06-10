<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Pos\Product;
use App\Models\Pos\ProductVariant;
use App\Services\Pos\PosInventoryService;
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
        $search = trim($request->string('search')->toString());

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
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(function (Product $product): array {
                $variant = $product->defaultVariant;

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $variant?->sku ?? $product->sku,
                    'status' => $product->status,
                    'product_variant_id' => $variant?->id,
                    'price' => (float) ($variant?->price ?? 0),
                    'cost_price' => $variant?->cost_price ? (float) $variant->cost_price : null,
                    'track_inventory' => (bool) $variant?->track_inventory,
                    'allow_backorder' => (bool) $variant?->allow_backorder,
                    'stock' => (float) ($variant?->stock?->quantity_on_hand ?? 0),
                    'created_at' => $product->created_at?->toISOString(),
                ];
            });

        return Inertia::render('pos/products', [
            'filters' => ['search' => $search],
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
}
