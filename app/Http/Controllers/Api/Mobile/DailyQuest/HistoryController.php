<?php

namespace App\Http\Controllers\Api\Mobile\DailyQuest;

use App\Http\Controllers\Controller;
use App\Http\Resources\DailyQuest\TaskCategoryResource;
use App\Http\Resources\DailyQuest\TaskInstanceResource;
use App\Http\Resources\DailyQuest\TaskResource;
use App\Models\DailyQuest\TaskInstance;
use App\Support\DailyQuestPayload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HistoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = $this->filteredInstanceQuery($request);
        $instances = $query
            ->with(['task.category'])
            ->orderByDesc('scheduled_date')
            ->orderBy('created_at')
            ->get();

        $month = $request->string('month')->toString() ?: now($user->timezone)->format('Y-m');
        $monthStart = Carbon::createFromFormat('Y-m', $month, $user->timezone)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $calendar = $user->dailyStats()
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderBy('date')
            ->get()
            ->map(fn ($stat): array => [
                'date' => $stat->date?->toDateString(),
                'total_tasks' => $stat->total_tasks,
                'completed_tasks' => $stat->completed_tasks,
                'points_earned' => $stat->points_earned,
                'completion_rate' => $stat->total_tasks > 0
                    ? (int) round(($stat->completed_tasks / $stat->total_tasks) * 100)
                    : 0,
            ])
            ->values()
            ->all();

        $days = $instances
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

        return response()->json([
            'data' => $days,
            'calendar' => $calendar,
            'month' => $monthStart->format('Y-m'),
            'filters' => [
                'task_id' => $request->string('task_id')->toString(),
                'category_id' => $request->string('category_id')->toString(),
                'from' => $request->string('from')->toString(),
                'to' => $request->string('to')->toString(),
            ],
            'tasks' => TaskResource::collection(
                $user->tasks()->with('category')->orderBy('name')->get(),
            )->resolve(),
            'categories' => TaskCategoryResource::collection(
                $user->categories()->orderBy('name')->get(),
            )->resolve(),
        ]);
    }

    public function show(Request $request, string $date): JsonResponse
    {
        $parsedDate = Carbon::parse($date, $request->user()->timezone)->toDateString();

        $instances = $this->filteredInstanceQuery($request)
            ->whereDate('scheduled_date', $parsedDate)
            ->with(['task.category'])
            ->orderBy('created_at')
            ->get();

        abort_if($instances->isEmpty(), 404);

        return response()->json([
            'data' => [
                'date' => $parsedDate,
                'summary' => DailyQuestPayload::daySummary($instances),
                'instances' => TaskInstanceResource::collection($instances)->resolve(),
            ],
        ]);
    }

    private function filteredInstanceQuery(Request $request): Builder
    {
        $query = TaskInstance::query()->where('user_id', $request->user()->id);

        if ($request->filled('task_id')) {
            $taskId = $request->string('task_id')->toString();
            $query->whereHas('task', fn (Builder $taskQuery): Builder => $taskQuery->whereKey($taskId));
        }

        if ($request->filled('category_id')) {
            $categoryId = $request->string('category_id')->toString();
            $query->whereHas('task.category', fn (Builder $categoryQuery): Builder => $categoryQuery->whereKey($categoryId));
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
