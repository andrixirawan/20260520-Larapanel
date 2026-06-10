<?php

use App\Models\Pos\FinanceEntry;
use App\Models\Pos\InventoryMovement;
use App\Models\Pos\InventoryStock;
use App\Models\Pos\Payment;
use App\Models\Pos\Product;
use App\Models\Pos\ProductVariant;
use App\Models\Pos\Sale;
use App\Models\Pos\Shift;
use App\Models\User;
use App\Support\AccessControl;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('cashier can open POS terminal but cannot manage products', function () {
    $cashier = User::factory()->create();
    $cashier->assignRole(AccessControl::ROLE_CASHIER);

    $this->actingAs($cashier)
        ->get(route('pos.terminal'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('pos/terminal')
            ->where('openShift', null)
        );

    $this->actingAs($cashier)
        ->post(route('pos.products.store'), [
            'name' => 'Coffee',
            'sku' => 'COF-01',
            'price' => 15000,
        ])
        ->assertForbidden();
});

test('administrator can create a POS product with opening stock', function () {
    $admin = User::factory()->create();
    $admin->assignRole(AccessControl::ROLE_ADMINISTRATOR);

    $this->actingAs($admin)
        ->post(route('pos.products.store'), [
            'name' => 'Americano',
            'sku' => 'AMR-01',
            'price' => 18000,
            'cost_price' => 9000,
            'initial_quantity' => 12,
            'track_inventory' => true,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $product = Product::query()->where('sku', 'AMR-01')->firstOrFail();
    $variant = ProductVariant::query()->where('product_id', $product->id)->firstOrFail();

    expect((float) $variant->price)->toBe(18000.0)
        ->and((float) $variant->stock->quantity_on_hand)->toBe(12.0);

    $this->assertDatabaseHas('pos_inventory_movements', [
        'product_variant_id' => $variant->id,
        'type' => InventoryMovement::TYPE_OPENING,
    ]);

    $this->assertDatabaseHas('pos_audit_logs', [
        'actor_id' => $admin->id,
        'event' => 'pos.product.created',
    ]);
});

test('cashier sale requires an open shift and records invoice stock finance and audit entries', function () {
    $cashier = User::factory()->create(['name' => 'Cashier One']);
    $cashier->assignRole(AccessControl::ROLE_CASHIER);
    $variant = createPosProductVariant(stock: 5, price: 10000);

    $this->actingAs($cashier)
        ->post(route('pos.sales.store'), [
            'items' => [
                ['product_variant_public_id' => $variant->public_id, 'quantity' => 2],
            ],
            'payment_method' => Payment::METHOD_CASH,
            'received_amount' => 25000,
        ])
        ->assertSessionHasErrors('shift');

    $this->actingAs($cashier)
        ->post(route('pos.shifts.store'), [
            'opening_cash' => 50000,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $shift = Shift::query()->where('cashier_id', $cashier->id)->firstOrFail();

    $this->actingAs($cashier)
        ->post(route('pos.sales.store'), [
            'items' => [
                ['product_variant_public_id' => $variant->public_id, 'quantity' => 2],
            ],
            'payment_method' => Payment::METHOD_CASH,
            'received_amount' => 25000,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $sale = Sale::query()->with(['items', 'payments'])->firstOrFail();

    expect($sale->invoice_number)->toStartWith('INV-')
        ->and((float) $sale->total)->toBe(20000.0)
        ->and((float) $sale->change_total)->toBe(5000.0)
        ->and($sale->items)->toHaveCount(1)
        ->and($sale->payments)->toHaveCount(1);

    expect((float) InventoryStock::query()->where('product_variant_id', $variant->id)->firstOrFail()->quantity_on_hand)
        ->toBe(3.0);

    $this->assertDatabaseHas('pos_finance_entries', [
        'shift_id' => $shift->id,
        'type' => FinanceEntry::TYPE_SALE_INCOME,
        'payment_method' => Payment::METHOD_CASH,
    ]);

    $this->assertDatabaseHas('pos_inventory_movements', [
        'product_variant_id' => $variant->id,
        'type' => InventoryMovement::TYPE_SALE,
        'reference_id' => $sale->id,
    ]);

    $this->assertDatabaseHas('pos_audit_logs', [
        'actor_id' => $cashier->id,
        'event' => 'pos.sale.created',
    ]);
});

test('cashier can close own shift with cash reconciliation', function () {
    $cashier = User::factory()->create();
    $cashier->assignRole(AccessControl::ROLE_CASHIER);
    $variant = createPosProductVariant(stock: 5, price: 10000);

    $this->actingAs($cashier)->post(route('pos.shifts.store'), [
        'opening_cash' => 50000,
    ]);

    $this->actingAs($cashier)->post(route('pos.sales.store'), [
        'items' => [
            ['product_variant_public_id' => $variant->public_id, 'quantity' => 2],
        ],
        'payment_method' => Payment::METHOD_CASH,
        'received_amount' => 20000,
    ]);

    $shift = Shift::query()->where('cashier_id', $cashier->id)->firstOrFail();

    $this->actingAs($cashier)
        ->patch(route('pos.shifts.close', $shift), [
            'counted_cash' => 70000,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $shift->refresh();

    expect($shift->status)->toBe(Shift::STATUS_CLOSED)
        ->and((float) $shift->expected_cash)->toBe(70000.0)
        ->and((float) $shift->counted_cash)->toBe(70000.0)
        ->and((float) $shift->cash_difference)->toBe(0.0);
});

test('dummy qris payment completes with provider reference for future gateway flow', function () {
    $cashier = User::factory()->create();
    $cashier->assignRole(AccessControl::ROLE_CASHIER);
    $variant = createPosProductVariant(stock: 5, price: 15000);

    $this->actingAs($cashier)->post(route('pos.shifts.store'), [
        'opening_cash' => 0,
    ]);

    $this->actingAs($cashier)
        ->post(route('pos.sales.store'), [
            'items' => [
                ['product_variant_public_id' => $variant->public_id, 'quantity' => 1],
            ],
            'payment_method' => Payment::METHOD_QRIS_DUMMY,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $payment = Payment::query()->firstOrFail();

    expect($payment->provider)->toBe('dummy')
        ->and($payment->provider_reference)->toStartWith('DUMMY-')
        ->and((float) $payment->change_amount)->toBe(0.0);
});

test('administrator can view shift and finance screens while cashier cannot view finance', function () {
    $admin = User::factory()->create();
    $admin->assignRole(AccessControl::ROLE_ADMINISTRATOR);
    $cashier = User::factory()->create();
    $cashier->assignRole(AccessControl::ROLE_CASHIER);

    $this->actingAs($admin)
        ->get(route('pos.shifts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('pos/shifts'));

    $this->actingAs($admin)
        ->get(route('pos.finance.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('pos/finance'));

    $this->actingAs($cashier)
        ->get(route('pos.finance.index'))
        ->assertForbidden();
});

function createPosProductVariant(float $stock, float $price): ProductVariant
{
    $product = Product::create([
        'name' => 'Test Product',
        'sku' => fake()->unique()->bothify('SKU-####'),
        'status' => Product::STATUS_ACTIVE,
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'name' => 'Default',
        'sku' => fake()->unique()->bothify('VAR-####'),
        'price' => $price,
        'cost_price' => $price / 2,
        'track_inventory' => true,
        'allow_backorder' => false,
        'is_default' => true,
    ]);

    InventoryStock::create([
        'product_variant_id' => $variant->id,
        'quantity_on_hand' => $stock,
        'quantity_reserved' => 0,
    ]);

    return $variant->load(['product', 'stock']);
}
