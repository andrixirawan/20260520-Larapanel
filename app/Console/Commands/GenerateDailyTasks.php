<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\DailyQuest\TaskSchedulerService;
use Illuminate\Console\Command;

class GenerateDailyTasks extends Command
{
    /**
     * @var string
     */
    protected $signature = 'tasks:generate-daily';

    /**
     * @var string
     */
    protected $description = 'Generate daily quest task instances for every user based on their timezone.';

    public function handle(TaskSchedulerService $taskSchedulerService): int
    {
        $generatedCount = 0;

        User::query()
            ->select(['id', 'timezone'])
            ->lazyById()
            ->each(function (User $user) use ($taskSchedulerService, &$generatedCount): void {
                $generatedCount += $taskSchedulerService->generateForDate(
                    $user,
                    now($user->timezone)->startOfDay(),
                );
            });

        $this->info("Generated {$generatedCount} task instance(s).");

        return self::SUCCESS;
    }
}
