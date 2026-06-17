<?php

namespace App\Services\DailyQuest;

use App\Models\DailyQuest\Task;
use App\Models\DailyQuest\TaskInstance;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TaskSchedulerService
{
    public function generateForDate(User $user, CarbonInterface $date): int
    {
        $scheduledDate = Carbon::instance($date)->startOfDay();

        $tasks = Task::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->get();

        $generatedCount = 0;

        foreach ($tasks as $task) {
            if (! $this->shouldRunOn($task, $scheduledDate)) {
                continue;
            }

            $instance = TaskInstance::query()
                ->where('task_id', $task->id)
                ->whereDate('scheduled_date', $scheduledDate->toDateString())
                ->first();

            if ($instance) {
                continue;
            }

            $instance = TaskInstance::query()->create([
                'task_id' => $task->id,
                'scheduled_date' => $scheduledDate->toDateString(),
                'user_id' => $user->id,
            ]);

            if ($instance->exists) {
                $generatedCount++;
            }
        }

        return $generatedCount;
    }

    public function shouldRunOn(Task $task, CarbonInterface $date): bool
    {
        $targetDate = Carbon::instance($date)->startOfDay();

        if (! $task->is_active || $task->trashed()) {
            return false;
        }

        return match ($task->recurrence_type) {
            'daily' => true,
            'specific_days' => $this->matchesSpecificDay($task->recurrence_days, $targetDate),
            'one_time' => $this->isSameScheduledDate($task->recurrence_starts_at, $targetDate),
            'x_days' => $this->isWithinXDaysWindow($task, $targetDate),
            'date_range' => $this->isWithinDateRange($task, $targetDate),
            default => false,
        };
    }

    public function catchUpForUser(User $user, ?CarbonInterface $today = null, int $maxDays = 7): int
    {
        $localToday = Carbon::instance($today ?? now($user->timezone))->startOfDay();
        $windowStart = $localToday->copy()->subDays(max($maxDays - 1, 0));
        $lastActiveDate = $user->last_active_date
            ? Carbon::parse($user->last_active_date->toDateString(), $user->timezone)->startOfDay()
            : null;

        if ($lastActiveDate && $lastActiveDate->greaterThanOrEqualTo($localToday)) {
            return 0;
        }

        $startDate = $lastActiveDate
            ? $lastActiveDate->copy()->addDay()->max($windowStart)
            : $localToday->copy();

        $generatedCount = 0;

        foreach ($this->datesBetween($startDate, $localToday) as $date) {
            $generatedCount += $this->generateForDate($user, $date);
        }

        return $generatedCount;
    }

    /**
     * @param  array<int, string>|null  $recurrenceDays
     */
    private function matchesSpecificDay(?array $recurrenceDays, CarbonInterface $date): bool
    {
        if ($recurrenceDays === null || $recurrenceDays === []) {
            return false;
        }

        return in_array($date->format('D'), $recurrenceDays, true);
    }

    private function isSameScheduledDate(?CarbonInterface $scheduledDate, CarbonInterface $date): bool
    {
        return $scheduledDate?->toDateString() === $date->toDateString();
    }

    private function isWithinXDaysWindow(Task $task, CarbonInterface $date): bool
    {
        $startDate = $task->recurrence_starts_at
            ? Carbon::instance($task->recurrence_starts_at)->startOfDay()
            : ($task->created_at ? Carbon::instance($task->created_at)->startOfDay() : null);
        $endDate = $task->recurrence_ends_at ? Carbon::instance($task->recurrence_ends_at)->startOfDay() : null;

        if (! $startDate || ! $endDate) {
            return false;
        }

        return $this->isDateStringWithinRange($date->toDateString(), $startDate->toDateString(), $endDate->toDateString());
    }

    private function isWithinDateRange(Task $task, CarbonInterface $date): bool
    {
        $startDate = $task->recurrence_starts_at ? Carbon::instance($task->recurrence_starts_at)->startOfDay() : null;
        $endDate = $task->recurrence_ends_at ? Carbon::instance($task->recurrence_ends_at)->startOfDay() : null;

        if (! $startDate || ! $endDate) {
            return false;
        }

        return $this->isDateStringWithinRange($date->toDateString(), $startDate->toDateString(), $endDate->toDateString());
    }

    /**
     * @return Collection<int, Carbon>
     */
    private function datesBetween(CarbonInterface $startDate, CarbonInterface $endDate): Collection
    {
        $dates = collect();
        $cursor = Carbon::parse($startDate->toDateString(), $startDate->getTimezone())->startOfDay();
        $endCursor = Carbon::parse($endDate->toDateString(), $startDate->getTimezone())->startOfDay();

        while ($cursor->lessThanOrEqualTo($endCursor)) {
            $dates->push($cursor->copy());
            $cursor->addDay();
        }

        return $dates;
    }

    private function isDateStringWithinRange(string $targetDate, string $startDate, string $endDate): bool
    {
        return $targetDate >= $startDate && $targetDate <= $endDate;
    }
}
