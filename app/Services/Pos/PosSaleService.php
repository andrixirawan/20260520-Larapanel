<?php

namespace App\Services\Pos;

use App\Models\Pos\FinanceEntry;
use App\Models\Pos\InventoryMovement;
use App\Models\Pos\InventoryStock;
use App\Models\Pos\Payment;
use App\Models\Pos\Product;
use App\Models\Pos\ProductVariant;
use App\Models\Pos\Sale;
use App\Models\Pos\Shift;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PosSaleService
{
    public function __construct(
        private readonly PosAuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(User $actor, array $payload): Sale
    {
        return DB::transaction(function () use ($actor, $payload): Sale {
            $shift = Shift::query()
                ->where('cashier_id', $actor->id)
                ->where('status', Shift::STATUS_OPEN)
                ->lockForUpdate()
                ->first();

            if (! $shift) {
                throw ValidationException::withMessages([
                    'shift' => __('Open a shift before creating a sale.'),
                ]);
            }

            $cartItems = $this->normalizedItems($payload['items'] ?? []);
            $variantIds = $cartItems->pluck('product_variant_id')->all();

            /** @var Collection<int, ProductVariant> $variants */
            $variants = ProductVariant::query()
                ->with('product')
                ->whereIn('id', $variantIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($variants->count() !== count($variantIds)) {
                throw ValidationException::withMessages([
                    'items' => __('One or more products are unavailable.'),
                ]);
            }

            $stocks = InventoryStock::query()
                ->whereIn('product_variant_id', $variantIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('product_variant_id');

            $subtotal = 0.0;
            $saleLines = [];

            foreach ($cartItems as $cartItem) {
                /** @var ProductVariant $variant */
                $variant = $variants->get($cartItem['product_variant_id']);
                /** @var Product $product */
                $product = $variant->product;

                if ($product->status !== Product::STATUS_ACTIVE) {
                    throw ValidationException::withMessages([
                        'items' => __('Product :product is inactive.', ['product' => $product->name]),
                    ]);
                }

                $quantity = (float) $cartItem['quantity'];
                $unitPrice = (float) $variant->price;
                $lineTotal = round($quantity * $unitPrice, 2);
                $subtotal = round($subtotal + $lineTotal, 2);

                if ($variant->track_inventory) {
                    /** @var InventoryStock|null $stock */
                    $stock = $stocks->get($variant->id);
                    $available = (float) ($stock?->quantity_on_hand ?? 0);

                    if ($available < $quantity && ! $variant->allow_backorder) {
                        throw ValidationException::withMessages([
                            'items' => __('Insufficient stock for :product.', ['product' => $product->name]),
                        ]);
                    }
                }

                $saleLines[] = [
                    'product' => $product,
                    'variant' => $variant,
                    'stock' => $stocks->get($variant->id),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
            }

            $total = $subtotal;
            $paymentMethod = (string) ($payload['payment_method'] ?? Payment::METHOD_CASH);
            $receivedAmount = $paymentMethod === Payment::METHOD_CASH
                ? round((float) ($payload['received_amount'] ?? 0), 2)
                : $total;

            if (! array_key_exists($paymentMethod, Payment::methodOptions())) {
                throw ValidationException::withMessages([
                    'payment_method' => __('Unsupported payment method.'),
                ]);
            }

            if ($receivedAmount < $total) {
                throw ValidationException::withMessages([
                    'received_amount' => __('Received amount must cover the sale total.'),
                ]);
            }

            $changeAmount = $paymentMethod === Payment::METHOD_CASH
                ? round($receivedAmount - $total, 2)
                : 0.0;

            $sale = Sale::create([
                'shift_id' => $shift->id,
                'cashier_id' => $actor->id,
                'invoice_number' => $this->invoiceNumber(),
                'status' => Sale::STATUS_COMPLETED,
                'payment_status' => Sale::PAYMENT_STATUS_PAID,
                'subtotal' => $this->money($subtotal),
                'discount_total' => '0.00',
                'tax_total' => '0.00',
                'total' => $this->money($total),
                'paid_total' => $this->money($total),
                'change_total' => $this->money($changeAmount),
                'customer_name' => $payload['customer_name'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'completed_at' => now(),
            ]);

            foreach ($saleLines as $line) {
                /** @var Product $product */
                $product = $line['product'];
                /** @var ProductVariant $variant */
                $variant = $line['variant'];

                $sale->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'sku_snapshot' => $variant->sku ?? $product->sku,
                    'name_snapshot' => $product->name.($variant->name && $variant->name !== 'Default' ? ' - '.$variant->name : ''),
                    'quantity' => $line['quantity'],
                    'unit_price' => $this->money($line['unit_price']),
                    'discount_total' => '0.00',
                    'tax_total' => '0.00',
                    'line_total' => $this->money($line['line_total']),
                    'cost_price_snapshot' => $variant->cost_price,
                ]);

                if ($variant->track_inventory) {
                    /** @var InventoryStock|null $stock */
                    $stock = $line['stock'];

                    if (! $stock) {
                        $stock = InventoryStock::create([
                            'product_variant_id' => $variant->id,
                            'quantity_on_hand' => 0,
                            'quantity_reserved' => 0,
                        ]);
                    }

                    $before = (float) $stock->quantity_on_hand;
                    $after = round($before - (float) $line['quantity'], 3);

                    $stock->update(['quantity_on_hand' => $after]);

                    InventoryMovement::create([
                        'product_variant_id' => $variant->id,
                        'actor_id' => $actor->id,
                        'type' => InventoryMovement::TYPE_SALE,
                        'quantity_before' => $before,
                        'quantity_delta' => -1 * (float) $line['quantity'],
                        'quantity_after' => $after,
                        'reference_type' => $sale->getMorphClass(),
                        'reference_id' => $sale->id,
                        'notes' => 'Sale '.$sale->invoice_number,
                    ]);
                }
            }

            $payment = $sale->payments()->create([
                'method' => $paymentMethod,
                'status' => Payment::STATUS_PAID,
                'amount' => $this->money($total),
                'received_amount' => $this->money($receivedAmount),
                'change_amount' => $this->money($changeAmount),
                'provider' => $paymentMethod === Payment::METHOD_CASH ? null : 'dummy',
                'provider_reference' => $paymentMethod === Payment::METHOD_CASH ? null : 'DUMMY-'.Str::upper(Str::random(10)),
                'metadata' => $paymentMethod === Payment::METHOD_CASH ? null : ['flow' => 'simulated-paid'],
            ]);

            FinanceEntry::create([
                'entry_date' => now()->toDateString(),
                'shift_id' => $shift->id,
                'source_type' => $payment->getMorphClass(),
                'source_id' => $payment->id,
                'type' => FinanceEntry::TYPE_SALE_INCOME,
                'direction' => FinanceEntry::DIRECTION_CREDIT,
                'payment_method' => $paymentMethod,
                'amount' => $this->money($total),
                'created_by' => $actor->id,
                'notes' => 'Sale '.$sale->invoice_number,
            ]);

            $this->auditLogger->log($actor, 'pos.sale.created', $sale, null, [
                'invoice_number' => $sale->invoice_number,
                'total' => $sale->total,
                'payment_method' => $paymentMethod,
            ]);

            return $sale->load(['items', 'payments', 'cashier', 'shift']);
        });
    }

    /**
     * @return Collection<int, array{product_variant_id: int, quantity: float}>
     */
    private function normalizedItems(mixed $items): Collection
    {
        $normalized = collect(is_array($items) ? $items : [])
            ->map(fn (array $item): array => [
                'product_variant_id' => (int) ($item['product_variant_id'] ?? 0),
                'quantity' => round((float) ($item['quantity'] ?? 0), 3),
            ])
            ->filter(fn (array $item): bool => $item['product_variant_id'] > 0 && $item['quantity'] > 0)
            ->groupBy('product_variant_id')
            ->map(fn (Collection $group, int $variantId): array => [
                'product_variant_id' => $variantId,
                'quantity' => round($group->sum('quantity'), 3),
            ])
            ->values();

        if ($normalized->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => __('Add at least one product to the cart.'),
            ]);
        }

        return $normalized;
    }

    private function invoiceNumber(): string
    {
        do {
            $number = 'INV-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(5));
        } while (Sale::query()->where('invoice_number', $number)->exists());

        return $number;
    }

    private function money(float|int|string $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
