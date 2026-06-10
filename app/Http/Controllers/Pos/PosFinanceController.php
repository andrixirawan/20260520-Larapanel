<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Pos\FinanceEntry;
use App\Support\TableQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosFinanceController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $search = TableQuery::search($request);
        $sortOptions = [
            'entry_date' => 'entry_date',
            'type' => 'type',
            'direction' => 'direction',
            'payment_method' => 'payment_method',
            'amount' => 'amount',
            'created_at' => 'created_at',
        ];
        $sort = TableQuery::sort($request, $sortOptions, 'created_at');
        $direction = TableQuery::direction($request);
        $perPage = TableQuery::perPage($request, 15);

        $entries = FinanceEntry::query()
            ->with(['shift:id,public_id,cashier_id,opened_at', 'creator:id,name'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder
                        ->where('type', 'like', "%{$search}%")
                        ->orWhere('direction', 'like', "%{$search}%")
                        ->orWhere('payment_method', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhere('public_id', 'like', "%{$search}%")
                        ->orWhereHas('creator', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('shift', fn ($shiftQuery) => $shiftQuery->where('public_id', 'like', "%{$search}%"));

                    if (is_numeric($search)) {
                        $builder->orWhere('shift_id', (int) $search);
                    }
                });
            })
            ->orderBy($sortOptions[$sort], $direction)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (FinanceEntry $entry): array => [
                'public_id' => $entry->public_id,
                'entry_date' => $entry->entry_date?->toDateString(),
                'shift_public_id' => $entry->shift?->public_id,
                'type' => $entry->type,
                'direction' => $entry->direction,
                'payment_method' => $entry->payment_method,
                'amount' => (float) $entry->amount,
                'created_by' => $entry->creator?->name,
                'notes' => $entry->notes,
                'created_at' => $entry->created_at?->toISOString(),
            ]);

        return Inertia::render('pos/finance', [
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
            'entries' => $entries,
        ]);
    }
}
