<?php

namespace App\Http\Controllers\DailyQuest;

use App\Http\Controllers\Controller;
use App\Models\DailyQuest\TaskInstance;
use App\Services\DailyQuest\TaskSchedulerService;
use App\Support\DailyQuestPayload;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly TaskSchedulerService $taskSchedulerService,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $today = now($user->timezone)->startOfDay();

        $this->taskSchedulerService->catchUpForUser($user, $today);

        $weeklyInstances = TaskInstance::query()
            ->where('user_id', $user->id)
            ->whereBetween('scheduled_date', [
                $today->copy()->subDays(6)->toDateString(),
                $today->toDateString(),
            ])
            ->with(['task.category'])
            ->orderBy('scheduled_date')
            ->get();

        return Inertia::render('dashboard', [
            'stats' => DailyQuestPayload::dashboard($user->fresh(), $weeklyInstances),
            'date_range' => DailyQuestPayload::dateRangeOptions($user),
        ]);
    }
}
