<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Pos\Payment;
use App\Models\Pos\Product;
use App\Models\Pos\Sale;
use App\Models\Pos\Shift;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosTerminalController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $products = Product::query()
            ->with(['defaultVariant.stock'])
            ->where('status', Product::STATUS_ACTIVE)
            ->whereHas('defaultVariant')
            ->orderBy('name')
            ->get()
            ->map(function (Product $product): array {
                $variant = $product->defaultVariant;

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $variant?->sku ?? $product->sku,
                    'product_variant_id' => $variant?->id,
                    'price' => (float) ($variant?->price ?? 0),
                    'track_inventory' => (bool) $variant?->track_inventory,
                    'stock' => (float) ($variant?->stock?->quantity_on_hand ?? 0),
                ];
            })
            ->values();

        $openShift = Shift::query()
            ->where('cashier_id', $user->id)
            ->where('status', Shift::STATUS_OPEN)
            ->latest('opened_at')
            ->first();

        $recentSales = Sale::query()
            ->with('payments:id,sale_id,method,amount')
            ->where('cashier_id', $user->id)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Sale $sale): array => [
                'id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'total' => (float) $sale->total,
                'payment_method' => $sale->payments->first()?->method,
                'created_at' => $sale->created_at?->toISOString(),
            ]);

        return Inertia::render('pos/terminal', [
            'products' => $products,
            'openShift' => $openShift ? [
                'id' => $openShift->id,
                'opening_cash' => (float) $openShift->opening_cash,
                'opened_at' => $openShift->opened_at?->toISOString(),
            ] : null,
            'paymentMethods' => Payment::methodOptions(),
            'recentSales' => $recentSales,
        ]);
    }
}
