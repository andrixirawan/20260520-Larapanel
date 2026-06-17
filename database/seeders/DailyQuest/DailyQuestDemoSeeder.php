<?php

namespace Database\Seeders\DailyQuest;

use App\Jobs\DailyQuest\UpdateUserStatsJob;
use App\Models\DailyQuest\Task;
use App\Models\DailyQuest\TaskCategory;
use App\Models\DailyQuest\TaskInstance;
use App\Models\DailyQuest\UserDailyStat;
use App\Models\User;
use App\Services\DailyQuest\TaskSchedulerService;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DailyQuestDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $user = User::query()->findOrFail(1);
            $timezone = $user->timezone ?: 'Asia/Jakarta';
            $today = now($timezone)->startOfDay();
            $yesterday = $today->copy()->subDay();
            $twoDaysAgo = $today->copy()->subDays(2);

            $this->purgeExistingDailyQuestData($user);

            $user->forceFill([
                'timezone' => $timezone,
                'total_points' => 0,
                'current_streak' => 0,
                'longest_streak' => 0,
                'last_active_date' => null,
            ])->save();

            $categories = [
                'health' => $user->categories()->create([
                    'name' => 'Health',
                    'color' => '#22c55e',
                    'icon' => '💪',
                ]),
                'focus' => $user->categories()->create([
                    'name' => 'Focus',
                    'color' => '#0f766e',
                    'icon' => '🎯',
                ]),
                'home' => $user->categories()->create([
                    'name' => 'Home',
                    'color' => '#f97316',
                    'icon' => '🏠',
                ]),
                'learning' => $user->categories()->create([
                    'name' => 'Learning',
                    'color' => '#2563eb',
                    'icon' => '📚',
                ]),
            ];

            $tasks = [
                'morning_run' => $this->createTask($user, [
                    'category_id' => $categories['health']->id,
                    'name' => 'Morning Run',
                    'description' => 'Jogging ringan 25 menit sebelum mulai kerja.',
                    'icon' => '🏃',
                    'color' => '#16a34a',
                    'points' => 20,
                    'recurrence_type' => 'daily',
                ], $today->copy()->subDays(10)),
                'meditation' => $this->createTask($user, [
                    'category_id' => $categories['health']->id,
                    'name' => 'Meditation Reset',
                    'description' => 'Tarik napas, 10 menit tanpa notifikasi.',
                    'icon' => '🧘',
                    'color' => '#14b8a6',
                    'points' => 15,
                    'recurrence_type' => 'daily',
                ], $today->copy()->subDays(10)),
                'deep_work' => $this->createTask($user, [
                    'category_id' => $categories['focus']->id,
                    'name' => 'Deep Work Block',
                    'description' => 'Blok fokus 90 menit tanpa meeting.',
                    'icon' => '🧠',
                    'color' => '#0f766e',
                    'points' => 30,
                    'recurrence_type' => 'specific_days',
                    'recurrence_days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                ], $today->copy()->subDays(10)),
                'inbox_zero' => $this->createTask($user, [
                    'category_id' => $categories['focus']->id,
                    'name' => 'Inbox Zero Sprint',
                    'description' => 'Rapikan email dan DM prioritas.',
                    'icon' => '📥',
                    'color' => '#0891b2',
                    'points' => 18,
                    'recurrence_type' => 'specific_days',
                    'recurrence_days' => ['Tue', 'Thu', 'Sat'],
                ], $today->copy()->subDays(10)),
                'dishwasher' => $this->createTask($user, [
                    'category_id' => $categories['home']->id,
                    'name' => 'Load Dishwasher',
                    'description' => 'Rapikan dapur sebelum tidur.',
                    'icon' => '🍽️',
                    'color' => '#f97316',
                    'points' => 8,
                    'recurrence_type' => 'daily',
                ], $today->copy()->subDays(10)),
                'desk_reset' => $this->createTask($user, [
                    'category_id' => $categories['home']->id,
                    'name' => 'Desk Reset',
                    'description' => 'Rapikan meja kerja selama sprint mingguan.',
                    'icon' => '🧽',
                    'color' => '#fb923c',
                    'points' => 12,
                    'recurrence_type' => 'date_range',
                    'recurrence_starts_at' => $twoDaysAgo->toDateString(),
                    'recurrence_ends_at' => $today->toDateString(),
                ], $twoDaysAgo),
                'feature_launch' => $this->createTask($user, [
                    'category_id' => $categories['learning']->id,
                    'name' => 'Feature Launch Sprint',
                    'description' => 'Checklist launch untuk eksperimen daily quest.',
                    'icon' => '🚀',
                    'color' => '#6366f1',
                    'points' => 28,
                    'recurrence_type' => 'x_days',
                    'recurrence_starts_at' => $twoDaysAgo->toDateString(),
                    'recurrence_ends_at' => $today->toDateString(),
                ], $twoDaysAgo),
                'read_book' => $this->createTask($user, [
                    'category_id' => $categories['learning']->id,
                    'name' => 'Read 20 Pages',
                    'description' => 'Minimal 20 halaman buku teknis atau bisnis.',
                    'icon' => '📖',
                    'color' => '#2563eb',
                    'points' => 16,
                    'recurrence_type' => 'specific_days',
                    'recurrence_days' => ['Mon', 'Wed', 'Fri', 'Sun'],
                ], $today->copy()->subDays(10)),
                'journal' => $this->createTask($user, [
                    'category_id' => $categories['learning']->id,
                    'name' => 'Journal Reflection',
                    'description' => 'Tulis tiga hal penting dari hari ini.',
                    'icon' => '📝',
                    'color' => '#7c3aed',
                    'points' => 14,
                    'recurrence_type' => 'daily',
                ], $today->copy()->subDays(10)),
            ];

            $pausedTask = $this->createTask($user, [
                'category_id' => $categories['focus']->id,
                'name' => 'Evening Review',
                'description' => 'Review KPI harian, sementara dijeda.',
                'icon' => '📊',
                'color' => '#475569',
                'points' => 12,
                'recurrence_type' => 'daily',
                'is_active' => false,
            ], $today->copy()->subDays(12));

            $archivedTask = $this->createTask($user, [
                'category_id' => $categories['home']->id,
                'name' => 'Water Balcony Plants',
                'description' => 'Task lama yang sudah diarsipkan tapi history tetap ada.',
                'icon' => '🪴',
                'color' => '#65a30d',
                'points' => 9,
                'recurrence_type' => 'daily',
            ], $today->copy()->subDays(12));

            $scheduler = app(TaskSchedulerService::class);

            foreach ([$twoDaysAgo, $yesterday, $today] as $date) {
                $scheduler->generateForDate($user, $date);
            }

            $this->createManualInstance($pausedTask, $user, $twoDaysAgo, true, 'Sempat aktif sebelum dipause.');
            $this->createManualInstance($pausedTask, $user, $yesterday, false, 'Task ini dipause mulai hari ini.');

            $archivedTask->delete();

            $completionMap = [
                $twoDaysAgo->toDateString() => [
                    'Morning Run' => true,
                    'Meditation Reset' => true,
                    'Deep Work Block' => true,
                    'Inbox Zero Sprint' => true,
                    'Load Dishwasher' => false,
                    'Desk Reset' => true,
                    'Feature Launch Sprint' => true,
                    'Read 20 Pages' => true,
                    'Journal Reflection' => true,
                ],
                $yesterday->toDateString() => [
                    'Morning Run' => true,
                    'Meditation Reset' => false,
                    'Deep Work Block' => true,
                    'Load Dishwasher' => true,
                    'Desk Reset' => true,
                    'Feature Launch Sprint' => true,
                    'Journal Reflection' => true,
                    'Water Balcony Plants' => true,
                ],
                $today->toDateString() => [
                    'Morning Run' => true,
                    'Meditation Reset' => true,
                    'Deep Work Block' => false,
                    'Inbox Zero Sprint' => false,
                    'Load Dishwasher' => false,
                    'Desk Reset' => false,
                    'Feature Launch Sprint' => true,
                    'Read 20 Pages' => false,
                    'Journal Reflection' => true,
                ],
            ];

            foreach ($completionMap as $date => $tasksForDate) {
                foreach ($tasksForDate as $taskName => $completed) {
                    $instance = TaskInstance::query()
                        ->where('user_id', $user->id)
                        ->whereDate('scheduled_date', $date)
                        ->whereHas('task', fn ($query) => $query->where('name', $taskName))
                        ->first();

                    if (! $instance) {
                        continue;
                    }

                    $instance->forceFill([
                        'completed_at' => $completed ? Carbon::parse($date, $timezone)->setTime(21, 0) : null,
                        'points_awarded' => $completed ? $instance->task?->points : null,
                        'notes' => $this->defaultNote($taskName, $date, $completed),
                    ])->save();
                }

                UpdateUserStatsJob::dispatchSync($user->id, $date);
            }

            $user->refresh();

            UserDailyStat::query()
                ->where('user_id', $user->id)
                ->orderBy('date')
                ->get()
                ->each(function (UserDailyStat $stat): void {
                    $stat->timestamps = false;
                    $stat->created_at = Carbon::parse($stat->date)->setTime(23, 15);
                    $stat->updated_at = Carbon::parse($stat->date)->setTime(23, 15);
                    $stat->save();
                });
        });
    }

    private function purgeExistingDailyQuestData(User $user): void
    {
        UserDailyStat::query()->where('user_id', $user->id)->delete();
        TaskInstance::query()->where('user_id', $user->id)->delete();

        Task::query()
            ->withTrashed()
            ->where('user_id', $user->id)
            ->get()
            ->each(function (Task $task): void {
                $task->forceDelete();
            });

        TaskCategory::query()->where('user_id', $user->id)->delete();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createTask(User $user, array $attributes, CarbonInterface $createdAt): Task
    {
        $task = $user->tasks()->create(array_merge([
            'description' => null,
            'icon' => null,
            'color' => null,
            'points' => 10,
            'recurrence_days' => null,
            'recurrence_starts_at' => null,
            'recurrence_ends_at' => null,
            'is_active' => true,
        ], $attributes));

        $task->timestamps = false;
        $task->created_at = $createdAt;
        $task->updated_at = $createdAt;
        $task->save();

        return $task->fresh();
    }

    private function createManualInstance(
        Task $task,
        User $user,
        CarbonInterface $date,
        bool $completed,
        string $notes,
    ): void {
        TaskInstance::query()->create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'scheduled_date' => $date->toDateString(),
            'completed_at' => $completed ? $date->copy()->setTime(20, 30) : null,
            'points_awarded' => $completed ? $task->points : null,
            'notes' => $notes,
            'created_at' => $date->copy()->setTime(6, 0),
            'updated_at' => $date->copy()->setTime(20, 30),
        ]);
    }

    private function defaultNote(string $taskName, string $date, bool $completed): string
    {
        return $completed
            ? sprintf('%s selesai pada %s dengan progress yang bagus.', $taskName, $date)
            : sprintf('%s belum selesai pada %s.', $taskName, $date);
    }
}
