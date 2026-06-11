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
