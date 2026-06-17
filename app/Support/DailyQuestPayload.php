<?php

namespace App\Support;

use App\Models\DailyQuest\Task;
use App\Models\DailyQuest\TaskCategory;
use App\Models\DailyQuest\TaskInstance;
use App\Models\User;
use Illuminate\Support\Collection;

class DailyQuestPayload
{
    public static function category(TaskCategory $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'color' => $category->color,
            'icon' => $category->icon,
            'tasks_count' => $category->tasks_count,
            'created_at' => $category->created_at?->toIso8601String(),
            'updated_at' => $category->updated_at?->toIso8601String(),
        ];
    }

    public static function task(Task $task): array
    {
        return [
            'id' => $task->id,
            'name' => $task->name,
            'description' => $task->description,
            'icon' => $task->icon,
            'color' => $task->color,
            'points' => $task->points,
            'recurrence_type' => $task->recurrence_type,
            'recurrence_days' => $task->recurrence_days,
            'recurrence_starts_at' => $task->recurrence_starts_at?->toDateString(),
            'recurrence_ends_at' => $task->recurrence_ends_at?->toDateString(),
            'recurrence_summary' => self::recurrenceSummary($task),
            'is_active' => $task->is_active,
            'deleted_at' => $task->deleted_at?->toIso8601String(),
            'category' => $task->category ? self::category($task->category) : null,
            'created_at' => $task->created_at?->toIso8601String(),
            'updated_at' => $task->updated_at?->toIso8601String(),
        ];
    }

    public static function taskInstance(TaskInstance $instance): array
    {
        return [
            'id' => $instance->id,
            'scheduled_date' => $instance->scheduled_date?->toDateString(),
            'completed_at' => $instance->completed_at?->toIso8601String(),
            'points_awarded' => $instance->points_awarded,
            'notes' => $instance->notes,
            'task' => $instance->task ? self::task($instance->task) : null,
            'created_at' => $instance->created_at?->toIso8601String(),
            'updated_at' => $instance->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @param  Collection<int, TaskInstance>  $instances
     */
    public static function daySummary(Collection $instances): array
    {
        $completedInstances = $instances->filter(fn (TaskInstance $instance): bool => $instance->completed_at !== null);

        return [
            'date' => $instances->first()?->scheduled_date?->toDateString(),
            'total_tasks' => $instances->count(),
            'completed_tasks' => $completedInstances->count(),
            'points_earned' => (int) $completedInstances->sum('points_awarded'),
            'completion_rate' => $instances->count() > 0
                ? (int) round(($completedInstances->count() / $instances->count()) * 100)
                : 0,
        ];
    }

    public static function dashboard(User $user, Collection $weeklyInstances): array
    {
        $todayDate = now($user->timezone)->toDateString();
        $todayInstances = $weeklyInstances->where('scheduled_date', $todayDate)->values();
        $completedInstances = $weeklyInstances->filter(fn (TaskInstance $instance): bool => $instance->completed_at !== null);

        return [
            'streak' => [
                'current' => $user->current_streak,
                'longest' => $user->longest_streak,
            ],
            'points' => [
                'total' => $user->total_points,
                'today' => (int) $todayInstances->whereNotNull('completed_at')->sum('points_awarded'),
            ],
            'today' => [
                'date' => $todayDate,
                'total_tasks' => $todayInstances->count(),
                'completed_tasks' => $todayInstances->whereNotNull('completed_at')->count(),
            ],
            'weekly_completion_rate' => $weeklyInstances->count() > 0
                ? (int) round(($completedInstances->count() / $weeklyInstances->count()) * 100)
                : 0,
            'weekly_chart' => self::weeklyChart($weeklyInstances),
        ];
    }

    /**
     * @param  Collection<int, TaskInstance>  $instances
     * @return array<int, array<string, int|string>>
     */
    public static function weeklyChart(Collection $instances): array
    {
        return $instances
            ->groupBy(fn (TaskInstance $instance): string => $instance->scheduled_date->toDateString())
            ->sortKeys()
            ->map(function (Collection $group, string $date): array {
                $completed = $group->whereNotNull('completed_at');

                return [
                    'date' => $date,
                    'total_tasks' => $group->count(),
                    'completed_tasks' => $completed->count(),
                    'points_earned' => (int) $completed->sum('points_awarded'),
                ];
            })
            ->values()
            ->all();
    }

    public static function recurrenceSummary(Task $task): string
    {
        return match ($task->recurrence_type) {
            'daily' => 'Daily',
            'specific_days' => collect($task->recurrence_days ?? [])->implode(', '),
            'one_time' => 'One time: '.($task->recurrence_starts_at?->toDateString() ?? '-'),
            'x_days' => sprintf(
                '%s until %s',
                $task->recurrence_starts_at?->toDateString() ?? $task->created_at?->toDateString() ?? '-',
                $task->recurrence_ends_at?->toDateString() ?? '-',
            ),
            'date_range' => sprintf(
                '%s to %s',
                $task->recurrence_starts_at?->toDateString() ?? '-',
                $task->recurrence_ends_at?->toDateString() ?? '-',
            ),
            default => ucfirst(str_replace('_', ' ', $task->recurrence_type)),
        };
    }

    public static function dateRangeOptions(User $user): array
    {
        $today = now($user->timezone)->toDateString();

        return [
            'today' => $today,
            'week_start' => now($user->timezone)->startOfWeek()->toDateString(),
            'week_end' => now($user->timezone)->endOfWeek()->toDateString(),
        ];
    }
}
