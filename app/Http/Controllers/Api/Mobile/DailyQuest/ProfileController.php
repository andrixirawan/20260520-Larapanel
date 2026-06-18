<?php

namespace App\Http\Controllers\Api\Mobile\DailyQuest;

use App\Http\Controllers\Controller;
use App\Http\Requests\DailyQuest\UpdateDisplayNameRequest;
use App\Models\DailyQuest\TaskInstance;
use App\Support\DailyQuestPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
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

        return response()->json([
            'data' => [
                'user' => [
                    'public_id' => $user->public_id,
                    'name' => $user->name,
                    'avatar' => $user->avatar,
                    'timezone' => $user->timezone,
                ],
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
            ],
        ]);
    }

    public function updateDisplayName(UpdateDisplayNameRequest $request): JsonResponse
    {
        $request->user()->update([
            'name' => $request->string('name')->trim()->toString(),
        ]);

        return response()->json([
            'message' => 'Display name updated.',
            'data' => [
                'name' => $request->user()->fresh()->name,
            ],
        ]);
    }
}
