<?php

namespace App\Http\Controllers\DailyQuest;

use App\Http\Controllers\Controller;
use App\Http\Resources\DailyQuest\TaskCategoryResource;
use App\Http\Resources\DailyQuest\TaskInstanceResource;
use App\Http\Resources\DailyQuest\TaskResource;
use App\Models\DailyQuest\TaskInstance;
use App\Support\DailyQuestPayload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class HistoryController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = $this->filteredInstanceQuery($request);

        $instances = $query
            ->with(['task.category'])
            ->orderByDesc('scheduled_date')
            ->orderBy('created_at')
            ->get();

        $groupedDays = $instances
            ->groupBy(fn (TaskInstance $instance): string => $instance->scheduled_date->toDateString())
            ->map(function ($dayInstances): array {
                $collection = collect($dayInstances);

                return [
                    'summary' => DailyQuestPayload::daySummary($collection),
                    'instances' => TaskInstanceResource::collection($collection)->resolve(),
                ];
            })
            ->values()
            ->all();

        return Inertia::render('daily-quest/history/index', [
            'days' => $groupedDays,
            'filters' => [
                'task_public_id' => $request->string('task_public_id')->toString(),
                'category_public_id' => $request->string('category_public_id')->toString(),
                'from' => $request->string('from')->toString(),
                'to' => $request->string('to')->toString(),
            ],
            'tasks' => TaskResource::collection($user->tasks()->with('category')->orderBy('name')->get())->resolve(),
            'categories' => TaskCategoryResource::collection($user->categories()->orderBy('name')->get())->resolve(),
        ]);
    }

    public function show(Request $request, string $date): Response
    {
        $parsedDate = Carbon::parse($date, $request->user()->timezone)->toDateString();

        $instances = $this->filteredInstanceQuery($request)
            ->whereDate('scheduled_date', $parsedDate)
            ->with(['task.category'])
            ->orderBy('created_at')
            ->get();

        abort_if($instances->isEmpty(), 404);

        return Inertia::render('daily-quest/history/show', [
            'date' => $parsedDate,
            'summary' => DailyQuestPayload::daySummary($instances),
            'instances' => TaskInstanceResource::collection($instances)->resolve(),
        ]);
    }

    private function filteredInstanceQuery(Request $request): Builder
    {
        $query = TaskInstance::query()->where('user_id', $request->user()->id);

        if ($request->filled('task_public_id')) {
            $taskPublicId = $request->string('task_public_id')->toString();
            $query->whereHas('task', fn (Builder $taskQuery): Builder => $taskQuery->where('public_id', $taskPublicId));
        }

        if ($request->filled('category_public_id')) {
            $categoryPublicId = $request->string('category_public_id')->toString();
            $query->whereHas('task.category', fn (Builder $categoryQuery): Builder => $categoryQuery->where('public_id', $categoryPublicId));
        }

        if ($request->filled('from')) {
            $query->whereDate('scheduled_date', '>=', $request->string('from')->toString());
        }

        if ($request->filled('to')) {
            $query->whereDate('scheduled_date', '<=', $request->string('to')->toString());
        }

        return $query;
    }
}
