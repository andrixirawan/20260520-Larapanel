<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\DailyQuest\TaskSchedulerService;
use Illuminate\Auth\Events\Login;

class GenerateDailyQuestCatchUpOnLogin
{
    public function __construct(
        private readonly TaskSchedulerService $taskSchedulerService,
    ) {}

    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $today = now($event->user->timezone)->startOfDay();

        $this->taskSchedulerService->catchUpForUser($event->user, $today);
    }
}
