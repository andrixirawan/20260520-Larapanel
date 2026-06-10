<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Pos\FinanceEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosFinanceController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $entries = FinanceEntry::query()
            ->with(['shift:id,cashier_id,opened_at', 'creator:id,name'])
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (FinanceEntry $entry): array => [
                'id' => $entry->id,
                'entry_date' => $entry->entry_date?->toDateString(),
                'shift_id' => $entry->shift_id,
                'type' => $entry->type,
                'direction' => $entry->direction,
                'payment_method' => $entry->payment_method,
                'amount' => (float) $entry->amount,
                'created_by' => $entry->creator?->name,
                'notes' => $entry->notes,
                'created_at' => $entry->created_at?->toISOString(),
            ]);

        return Inertia::render('pos/finance', [
            'entries' => $entries,
        ]);
    }
}
