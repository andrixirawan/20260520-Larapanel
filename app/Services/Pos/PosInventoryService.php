<?php

namespace App\Services\Pos;

use App\Models\Pos\InventoryMovement;
use App\Models\Pos\InventoryStock;
use App\Models\Pos\Product;
use App\Models\Pos\ProductVariant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosInventoryService
{
    public function __construct(
        private readonly PosAuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createProduct(User $actor, array $data): Product
    {
        return DB::transaction(function () use ($actor, $data): Product {
            $sku = $this->resolveSku($data);

            $product = Product::create([
                'name' => $data['name'],
                'sku' => $sku,
                'status' => $data['status'] ?? Product::STATUS_ACTIVE,
                'description' => $data['description'] ?? null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'name' => 'Default',
                'sku' => $data['variant_sku'] ?? $sku,
                'barcode' => $data['barcode'] ?? null,
                'price' => $this->money($data['price']),
                'cost_price' => isset($data['cost_price']) ? $this->money($data['cost_price']) : null,
                'track_inventory' => (bool) ($data['track_inventory'] ?? true),
                'allow_backorder' => (bool) ($data['allow_backorder'] ?? false),
                'low_stock_threshold' => $this->quantity($data['low_stock_threshold'] ?? 0),
                'is_default' => true,
            ]);

            InventoryStock::create([
                'product_variant_id' => $variant->id,
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
            ]);

            $initialQuantity = $this->quantity($data['initial_quantity'] ?? 0);
            if ($initialQuantity !== 0.0) {
                $this->moveStock(
                    actor: $actor,
                    variant: $variant,
                    delta: $initialQuantity,
                    type: InventoryMovement::TYPE_OPENING,
                    reference: $product,
                    notes: 'Initial stock',
                );
            }

            $this->auditLogger->log($actor, 'pos.product.created', $product, null, [
                'id' => $product->id,
                'default_variant_id' => $variant->id,
            ]);

            return $product->load('defaultVariant.stock');
        });
    }

    public function adjustStock(User $actor, ProductVariant $variant, float|int|string $delta, ?string $notes = null): InventoryMovement
    {
        $quantityDelta = $this->quantity($delta);

        if ($quantityDelta === 0.0) {
            throw ValidationException::withMessages([
                'quantity_delta' => __('Stock adjustment cannot be zero.'),
            ]);
        }

        return DB::transaction(function () use ($actor, $variant, $quantityDelta, $notes): InventoryMovement {
            $movement = $this->moveStock(
                actor: $actor,
                variant: $variant,
                delta: $quantityDelta,
                type: InventoryMovement::TYPE_ADJUSTMENT,
                notes: $notes,
            );

            $this->auditLogger->log($actor, 'pos.inventory.adjusted', $variant, null, [
                'product_variant_id' => $variant->id,
                'quantity_delta' => $quantityDelta,
                'quantity_after' => $movement->quantity_after,
            ]);

            return $movement;
        });
    }

    /**
     * @param  array<int, array{product_variant_id: int, counted_quantity: float|int|string}>  $items
     * @return array{adjusted: int, unchanged: int}
     */
    public function batchStockOpname(User $actor, array $items, ?string $notes = null): array
    {
        return DB::transaction(function () use ($actor, $items, $notes): array {
            $adjusted = 0;
            $unchanged = 0;
            $variants = ProductVariant::query()
                ->with(['product', 'stock'])
                ->whereIn('id', collect($items)->pluck('product_variant_id')->all())
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $variant = $variants->get($item['product_variant_id']);

                if (! $variant) {
                    continue;
                }

                $stock = InventoryStock::query()
                    ->where('product_variant_id', $variant->id)
                    ->lockForUpdate()
                    ->first();

                if (! $stock) {
                    $stock = InventoryStock::create([
                        'product_variant_id' => $variant->id,
                        'quantity_on_hand' => 0,
                        'quantity_reserved' => 0,
                    ]);
                }

                $countedQuantity = $this->quantity($item['counted_quantity']);
                $currentQuantity = round((float) $stock->quantity_on_hand, 3);
                $delta = round($countedQuantity - $currentQuantity, 3);

                if ($delta === 0.0) {
                    $unchanged++;
                    continue;
                }

                $movement = $this->moveStock(
                    actor: $actor,
                    variant: $variant,
                    delta: $delta,
                    type: InventoryMovement::TYPE_STOCK_OPNAME,
                    notes: $notes,
                );

                $this->auditLogger->log($actor, 'pos.inventory.stock_opname_recorded', $variant, null, [
                    'product_variant_id' => $variant->id,
                    'quantity_before' => $movement->quantity_before,
                    'counted_quantity' => $countedQuantity,
                    'quantity_after' => $movement->quantity_after,
                ]);

                $adjusted++;
            }

            return [
                'adjusted' => $adjusted,
                'unchanged' => $unchanged,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProduct(User $actor, Product $product, array $data): Product
    {
        return DB::transaction(function () use ($actor, $product, $data): Product {
            $product->loadMissing('defaultVariant');

            $variant = $product->defaultVariant;

            if (! $variant) {
                throw ValidationException::withMessages([
                    'product' => __('Default variant not found for this product.'),
                ]);
            }

            $sku = trim((string) ($data['sku'] ?? ''));
            if ($sku === '') {
                $sku = $product->sku ?: $variant->sku ?: $this->resolveSku($data);
            }

            $beforeProduct = $product->toArray();
            $beforeVariant = $variant->toArray();

            $product->update([
                'name' => $data['name'],
                'sku' => $sku,
                'status' => $data['status'] ?? $product->status,
                'description' => $data['description'] ?? null,
                'updated_by' => $actor->id,
            ]);

            $variant->update([
                'sku' => $sku,
                'price' => $this->money($data['price']),
                'cost_price' => isset($data['cost_price']) && $data['cost_price'] !== ''
                    ? $this->money($data['cost_price'])
                    : null,
                'track_inventory' => (bool) ($data['track_inventory'] ?? true),
                'allow_backorder' => (bool) ($data['allow_backorder'] ?? false),
                'low_stock_threshold' => $this->quantity($data['low_stock_threshold'] ?? 0),
            ]);

            $this->auditLogger->log($actor, 'pos.product.updated', $product, [
                'product' => $beforeProduct,
                'variant' => $beforeVariant,
            ], [
                'product' => $product->fresh()->toArray(),
                'variant' => $variant->fresh()->toArray(),
            ]);

            return $product->fresh()->load('defaultVariant.stock');
        });
    }

    public function deleteProduct(User $actor, Product $product): void
    {
        DB::transaction(function () use ($actor, $product): void {
            $product->loadMissing(['defaultVariant.saleItems', 'saleItems', 'variants']);

            $variant = $product->defaultVariant;
            $hasTransactions = $product->saleItems()->exists()
                || ($variant?->saleItems()->exists() ?? false);

            if ($hasTransactions) {
                throw ValidationException::withMessages([
                    'product' => __('This product already has sales history and cannot be deleted. Set it inactive instead.'),
                ]);
            }

            foreach ($product->variants as $item) {
                $item->delete();
            }

            $product->delete();

            $this->auditLogger->log($actor, 'pos.product.deleted', $product, $product->toArray(), null);
        });
    }

    public function moveStock(
        User $actor,
        ProductVariant $variant,
        float $delta,
        string $type,
        ?Model $reference = null,
        ?string $notes = null,
    ): InventoryMovement {
        $stock = InventoryStock::query()
            ->where('product_variant_id', $variant->id)
            ->lockForUpdate()
            ->first();

        if (! $stock) {
            $stock = InventoryStock::create([
                'product_variant_id' => $variant->id,
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
            ]);
            $stock->refresh();
        }

        $before = (float) $stock->quantity_on_hand;
        $after = round($before + $delta, 3);

        if ($after < 0 && ! $variant->allow_backorder) {
            throw ValidationException::withMessages([
                'items' => __('Insufficient stock for :product.', [
                    'product' => $variant->product?->name ?? $variant->sku ?? $variant->id,
                ]),
            ]);
        }

        $stock->update(['quantity_on_hand' => $after]);

        return InventoryMovement::create([
            'product_variant_id' => $variant->id,
            'actor_id' => $actor->id,
            'type' => $type,
            'quantity_before' => $before,
            'quantity_delta' => $delta,
            'quantity_after' => $after,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'notes' => $notes,
        ]);
    }

    private function money(float|int|string $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    private function quantity(float|int|string $value): float
    {
        return round((float) $value, 3);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveSku(array $data): string
    {
        $providedSku = trim((string) ($data['sku'] ?? ''));
        if ($providedSku !== '') {
            return $providedSku;
        }

        $prefix = 'PRD-';
        $nextNumber = $this->nextGeneratedSkuNumber($prefix);

        do {
            $candidate = sprintf('%s%06d', $prefix, $nextNumber);
            $exists = Product::query()->where('sku', $candidate)->exists()
                || ProductVariant::query()->where('sku', $candidate)->exists();
            $nextNumber++;
        } while ($exists);

        return $candidate;
    }

    private function nextGeneratedSkuNumber(string $prefix): int
    {
        $productSku = Product::query()
            ->where('sku', 'like', $prefix.'%')
            ->orderByDesc('sku')
            ->value('sku');

        $variantSku = ProductVariant::query()
            ->where('sku', 'like', $prefix.'%')
            ->orderByDesc('sku')
            ->value('sku');

        $lastNumber = max(
            $this->extractGeneratedSkuNumber($productSku, $prefix),
            $this->extractGeneratedSkuNumber($variantSku, $prefix),
        );

        return $lastNumber + 1;
    }

    private function extractGeneratedSkuNumber(mixed $sku, string $prefix): int
    {
        if (! is_string($sku)) {
            return 0;
        }

        $pattern = '/^'.preg_quote($prefix, '/').'(\d{6})$/';

        if (preg_match($pattern, $sku, $matches) !== 1) {
            return 0;
        }

        return (int) $matches[1];
    }
}
