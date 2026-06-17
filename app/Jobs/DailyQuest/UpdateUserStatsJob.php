<?php

namespace App\Jobs\DailyQuest;

use App\Models\DailyQuest\TaskInstance;
use App\Models\DailyQuest\UserDailyStat;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class UpdateUserStatsJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public readonly int $userId,
        public readonly ?string $date = null,
    ) {}

    public static function for(User $user, CarbonInterface|string|null $date = null): self
    {
        $resolvedDate = $date instanceof Carbon ? $date->toDateString() : $date;

        return new self($user->id, $resolvedDate);
    }

    public function handle(): void
    {
        $user = User::query()->find($this->userId);

        if (! $user) {
            return;
        }

        if ($this->date) {
            $this->syncDailyStat($user, $this->date);
        }

        $dailyCompletionDates = UserDailyStat::query()
            ->where('user_id', $user->id)
            ->where('completed_tasks', '>', 0)
            ->orderBy('date')
            ->pluck('date')
            ->map(fn (string $date): Carbon => Carbon::parse($date)->startOfDay())
            ->values();

        $lastActiveDate = $dailyCompletionDates->last()?->toDateString();
        $currentStreak = $this->calculateCurrentStreak($dailyCompletionDates);
        $longestStreak = $this->calculateLongestStreak($dailyCompletionDates);

        $user->forceFill([
            'total_points' => (int) TaskInstance::query()
                ->where('user_id', $user->id)
                ->whereNotNull('completed_at')
                ->sum('points_awarded'),
            'current_streak' => $currentStreak,
            'longest_streak' => max($user->longest_streak, $longestStreak),
            'last_active_date' => $lastActiveDate,
        ])->save();
    }

    private function syncDailyStat(User $user, string $date): void
    {
        $instanceQuery = TaskInstance::query()
            ->where('user_id', $user->id)
            ->whereDate('scheduled_date', $date);

        $totalTasks = (clone $instanceQuery)->count();

        if ($totalTasks === 0) {
            UserDailyStat::query()
                ->where('user_id', $user->id)
                ->whereDate('date', $date)
                ->delete();

            return;
        }

        $completedTasks = (clone $instanceQuery)
            ->whereNotNull('completed_at')
            ->count();

        $pointsEarned = (int) (clone $instanceQuery)
            ->whereNotNull('completed_at')
            ->sum('points_awarded');

        $dailyStat = UserDailyStat::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $date)
            ->first();

        if ($dailyStat) {
            $dailyStat->update([
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'points_earned' => $pointsEarned,
            ]);

            return;
        }

        UserDailyStat::query()->create([
            'user_id' => $user->id,
            'date' => $date,
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'points_earned' => $pointsEarned,
        ]);
    }

    /**
     * @param  Collection<int, Carbon>  $dates
     */
    private function calculateCurrentStreak($dates): int
    {
        if ($dates->isEmpty()) {
            return 0;
        }

        $streak = 1;
        $cursor = $dates->last();

        for ($index = $dates->count() - 2; $index >= 0; $index--) {
            $previous = $dates[$index];

            if (! $previous->isSameDay($cursor->copy()->subDay())) {
                break;
            }

            $streak++;
            $cursor = $previous;
        }

        return $streak;
    }

    /**
     * @param  Collection<int, Carbon>  $dates
     */
    private function calculateLongestStreak($dates): int
    {
        if ($dates->isEmpty()) {
            return 0;
        }

        $longestStreak = 1;
        $currentStreak = 1;

        for ($index = 1; $index < $dates->count(); $index++) {
            if ($dates[$index]->isSameDay($dates[$index - 1]->copy()->addDay())) {
                $currentStreak++;
            } else {
                $currentStreak = 1;
            }

            $longestStreak = max($longestStreak, $currentStreak);
        }

        return $longestStreak;
    }
}
