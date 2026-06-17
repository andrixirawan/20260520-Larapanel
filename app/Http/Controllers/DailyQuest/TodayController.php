<?php

namespace App\Http\Controllers\DailyQuest;

use App\Http\Controllers\Controller;
use App\Http\Resources\DailyQuest\TaskInstanceResource;
use App\Models\DailyQuest\TaskInstance;
use App\Services\DailyQuest\TaskSchedulerService;
use App\Support\DailyQuestPayload;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TodayController extends Controller
{
    public function __construct(
        private readonly TaskSchedulerService $taskSchedulerService,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $today = now($user->timezone)->startOfDay();

        $this->taskSchedulerService->catchUpForUser($user, $today);

        $instances = TaskInstance::query()
            ->where('user_id', $user->id)
            ->whereDate('scheduled_date', $today->toDateString())
            ->with(['task.category'])
            ->orderByRaw('completed_at is not null')
            ->orderBy('created_at')
            ->get();

        return Inertia::render('daily-quest/today/index', [
            'date' => $today->toDateString(),
            'instances' => TaskInstanceResource::collection($instances)->resolve(),
            'stats' => DailyQuestPayload::daySummary($instances),
            'streak' => $user->current_streak,
        ]);
    }
}
