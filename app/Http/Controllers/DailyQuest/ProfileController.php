<?php

namespace App\Http\Controllers\DailyQuest;

use App\Http\Controllers\Controller;
use App\Http\Requests\DailyQuest\UpdateDisplayNameRequest;
use App\Models\DailyQuest\TaskInstance;
use App\Support\DailyQuestPayload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $today = now($user->timezone)->startOfDay();
        $weekStart = $today->copy()->subDays(6)->toDateString();
        $todayDate = $today->toDateString();

        $weeklyInstances = TaskInstance::query()
            ->where('user_id', $user->id)
            ->whereBetween('scheduled_date', [$weekStart, $todayDate])
            ->get();

        $completedWeeklyInstances = $weeklyInstances->whereNotNull('completed_at');

        return Inertia::render('daily-quest/profile/index', [
            'stats' => [
                'streak' => [
                    'current' => $user->current_streak,
                    'longest' => $user->longest_streak,
                ],
                'points' => [
                    'total' => $user->total_points,
                ],
                'tasks' => [
                    'completed' => $user->taskInstances()->whereNotNull('completed_at')->count(),
                    'active' => $user->tasks()->where('is_active', true)->whereNull('deleted_at')->count(),
                ],
                'categories' => [
                    'total' => $user->categories()->count(),
                ],
                'weekly_completion_rate' => $weeklyInstances->count() > 0
                    ? (int) round(($completedWeeklyInstances->count() / $weeklyInstances->count()) * 100)
                    : 0,
            ],
            'date_range' => DailyQuestPayload::dateRangeOptions($user),
        ]);
    }

    public function updateDisplayName(UpdateDisplayNameRequest $request): RedirectResponse
    {
        $request->user()->update([
            'name' => $request->string('name')->trim()->toString(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Display name updated.')]);

        return to_route('daily-quest.profile');
    }
}
