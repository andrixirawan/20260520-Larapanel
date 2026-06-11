<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Pos\FinanceEntry;
use App\Models\Pos\Payment;
use App\Models\Pos\Product;
use App\Models\Pos\Sale;
use App\Models\Pos\Shift;
use App\Services\Pos\PosShiftService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosTerminalController extends Controller
{
    public function __construct(
        private readonly PosShiftService $shiftService,
    ) {}

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
                    'public_id' => $product->public_id,
                    'name' => $product->name,
                    'sku' => $variant?->sku ?? $product->sku,
                    'product_variant_public_id' => $variant?->public_id,
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
                'public_id' => $sale->public_id,
                'invoice_number' => $sale->invoice_number,
                'total' => (float) $sale->total,
                'payment_method' => $sale->payments->first()?->method,
                'created_at' => $sale->created_at?->toISOString(),
            ]);

        $recentCashMovements = $openShift
            ? FinanceEntry::query()
                ->where('shift_id', $openShift->id)
                ->whereIn('type', [FinanceEntry::TYPE_CASH_IN, FinanceEntry::TYPE_CASH_OUT])
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (FinanceEntry $entry): array => [
                    'public_id' => $entry->public_id,
                    'type' => $entry->type,
                    'amount' => (float) $entry->amount,
                    'notes' => $entry->notes,
                    'created_at' => $entry->created_at?->toISOString(),
                ])
                ->values()
            : [];

        return Inertia::render('pos/terminal', [
            'products' => $products,
            'openShift' => $openShift ? [
                'public_id' => $openShift->public_id,
                ...$this->shiftService->cashSnapshot($openShift),
                'opened_at' => $openShift->opened_at?->toISOString(),
            ] : null,
            'openingGuide' => $openShift ? null : $this->shiftService->openingGuide($user),
            'paymentMethods' => Payment::methodOptions(),
            'recentSales' => $recentSales,
            'recentCashMovements' => $recentCashMovements,
        ]);
    }
}
