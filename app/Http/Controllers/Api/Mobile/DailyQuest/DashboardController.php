<?php

namespace App\Http\Controllers\Api\Mobile\DailyQuest;

use App\Http\Controllers\Controller;
use App\Models\DailyQuest\TaskInstance;
use App\Services\DailyQuest\TaskSchedulerService;
use App\Support\DailyQuestPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
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

        $weeklyInstances = TaskInstance::query()
            ->where('user_id', $user->id)
            ->whereBetween('scheduled_date', [
                $today->copy()->subDays(6)->toDateString(),
                $today->toDateString(),
            ])
            ->with(['task.category'])
            ->orderBy('scheduled_date')
            ->get();

        return response()->json([
            'data' => [
                'stats' => DailyQuestPayload::dashboard($user->fresh(), $weeklyInstances),
                'date_range' => DailyQuestPayload::dateRangeOptions($user),
            ],
        ]);
    }
}
