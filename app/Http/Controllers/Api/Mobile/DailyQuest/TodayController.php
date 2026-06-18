<?php

namespace App\Http\Controllers\Api\Mobile\DailyQuest;

use App\Http\Controllers\Controller;
use App\Http\Resources\DailyQuest\TaskInstanceResource;
use App\Models\DailyQuest\TaskInstance;
use App\Services\DailyQuest\TaskSchedulerService;
use App\Support\DailyQuestPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TodayController extends Controller
{
    public function __construct(
        private readonly TaskSchedulerService $taskSchedulerService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = now($user->timezone)->startOfDay();

        $this->taskSchedulerService->catchUpForUser($user, $today);
        $this->taskSchedulerService->generateForDate($user, $today);

        $instances = TaskInstance::query()
            ->where('user_id', $user->id)
            ->whereDate('scheduled_date', $today->toDateString())
            ->with(['task.category'])
            ->orderByRaw('completed_at is not null')
            ->orderBy('created_at')
            ->get();

        $stats = DailyQuestPayload::daySummary($instances);

        return response()->json([
            'data' => [
                'date' => $today->toDateString(),
                'instances' => TaskInstanceResource::collection($instances)->resolve(),
                'stats' => $stats,
                'streak' => $user->current_streak,
                'all_completed' => $stats['total_tasks'] > 0 && $stats['total_tasks'] === $stats['completed_tasks'],
            ],
        ]);
    }
}
