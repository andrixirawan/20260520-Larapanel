<?php

use App\Jobs\DailyQuest\UpdateUserStatsJob;
use App\Models\DailyQuest\Task;
use App\Models\DailyQuest\TaskInstance;
use App\Models\User;
use App\Services\DailyQuest\TaskSchedulerService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

function makeTask(User $user, array $attributes = []): Task
{
    return Task::query()->create(array_merge([
        'user_id' => $user->id,
        'name' => 'Daily quest',
        'points' => 10,
        'recurrence_type' => 'daily',
        'is_active' => true,
    ], $attributes));
}

test('task scheduler generates instances for every supported recurrence type', function () {
    Carbon::setTestNow('2026-06-17 09:00:00');

    $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
    $service = app(TaskSchedulerService::class);
    $targetDate = Carbon::parse('2026-06-17', $user->timezone)->startOfDay();

    makeTask($user, ['name' => 'Daily']);
    makeTask($user, [
        'name' => 'Specific day',
        'recurrence_type' => 'specific_days',
        'recurrence_days' => ['Wed', 'Fri'],
    ]);
    makeTask($user, [
        'name' => 'One time',
        'recurrence_type' => 'one_time',
        'recurrence_starts_at' => '2026-06-17',
    ]);
    makeTask($user, [
        'name' => 'X days',
        'recurrence_type' => 'x_days',
        'recurrence_starts_at' => '2026-06-15',
        'recurrence_ends_at' => '2026-06-20',
    ]);
    makeTask($user, [
        'name' => 'Date range',
        'recurrence_type' => 'date_range',
        'recurrence_starts_at' => '2026-06-10',
        'recurrence_ends_at' => '2026-06-18',
    ]);
    makeTask($user, [
        'name' => 'Not today',
        'recurrence_type' => 'specific_days',
        'recurrence_days' => ['Mon'],
    ]);

    $generatedCount = $service->generateForDate($user, $targetDate);

    expect($generatedCount)->toBe(5)
        ->and(TaskInstance::query()->count())->toBe(5)
        ->and(TaskInstance::query()->whereDate('scheduled_date', '2026-06-17')->count())->toBe(5);

    $duplicateRun = $service->generateForDate($user, $targetDate);

    expect($duplicateRun)->toBe(0)
        ->and(TaskInstance::query()->count())->toBe(5);
});

test('daily task catch-up generates only missed days within the seven day window', function () {
    Carbon::setTestNow('2026-06-17 08:00:00');

    $user = User::factory()->create([
        'timezone' => 'Asia/Jakarta',
        'last_active_date' => '2026-06-07',
    ]);
    $service = app(TaskSchedulerService::class);

    makeTask($user, [
        'name' => 'Catch-up task',
        'recurrence_type' => 'daily',
    ]);

    $generatedCount = $service->catchUpForUser(
        $user,
        Carbon::parse('2026-06-17', $user->timezone)->startOfDay(),
    );

    expect($generatedCount)->toBe(7)
        ->and(TaskInstance::query()->count())->toBe(7)
        ->and(TaskInstance::query()->orderBy('scheduled_date')->firstOrFail()->scheduled_date->toDateString())->toBe('2026-06-11')
        ->and(TaskInstance::query()->orderByDesc('scheduled_date')->firstOrFail()->scheduled_date->toDateString())->toBe('2026-06-17');
});

test('login triggers daily quest catch-up generation for missed days', function () {
    Carbon::setTestNow('2026-06-17 07:00:00');
    Mail::fake();

    $user = User::factory()->create([
        'timezone' => 'Asia/Jakarta',
        'last_active_date' => '2026-06-15',
    ]);

    makeTask($user, [
        'name' => 'Login catch-up task',
        'recurrence_type' => 'daily',
    ]);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));

    expect(TaskInstance::query()->count())->toBe(2)
        ->and(
            TaskInstance::query()
                ->orderBy('scheduled_date')
                ->get()
                ->pluck('scheduled_date')
                ->map(fn ($date) => $date->toDateString())
                ->all(),
        )
        ->toBe(['2026-06-16', '2026-06-17']);
});

test('update user stats job recalculates daily stats and streak counters', function () {
    $user = User::factory()->create();
    $task = makeTask($user, ['name' => 'Tracked task']);

    TaskInstance::query()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'scheduled_date' => '2026-06-14',
        'completed_at' => '2026-06-14 07:00:00',
        'points_awarded' => 10,
    ]);
    TaskInstance::query()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'scheduled_date' => '2026-06-15',
        'completed_at' => '2026-06-15 07:00:00',
        'points_awarded' => 15,
    ]);
    TaskInstance::query()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'scheduled_date' => '2026-06-16',
        'completed_at' => '2026-06-16 07:00:00',
        'points_awarded' => 20,
    ]);
    TaskInstance::query()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'scheduled_date' => '2026-06-17',
        'completed_at' => null,
        'points_awarded' => null,
    ]);

    UpdateUserStatsJob::dispatchSync($user->id, '2026-06-14');
    UpdateUserStatsJob::dispatchSync($user->id, '2026-06-15');
    UpdateUserStatsJob::dispatchSync($user->id, '2026-06-16');
    UpdateUserStatsJob::dispatchSync($user->id, '2026-06-17');

    $freshUser = $user->fresh();

    expect($freshUser->total_points)->toBe(45)
        ->and($freshUser->current_streak)->toBe(3)
        ->and($freshUser->longest_streak)->toBe(3)
        ->and($freshUser->last_active_date?->toDateString())->toBe('2026-06-16');

    $this->assertDatabaseHas('user_daily_stats', [
        'user_id' => $user->id,
        'date' => '2026-06-16 00:00:00',
        'total_tasks' => 1,
        'completed_tasks' => 1,
        'points_earned' => 20,
    ]);

    $this->assertDatabaseHas('user_daily_stats', [
        'user_id' => $user->id,
        'date' => '2026-06-17 00:00:00',
        'total_tasks' => 1,
        'completed_tasks' => 0,
        'points_earned' => 0,
    ]);
});

test('daily generation command creates instances using each users local date', function () {
    Carbon::setTestNow('2026-06-17 00:05:00');

    $asiaUser = User::factory()->create(['timezone' => 'Asia/Jakarta']);
    $usUser = User::factory()->create(['timezone' => 'America/New_York']);

    makeTask($asiaUser, ['name' => 'Asia task']);
    makeTask($usUser, ['name' => 'US task']);

    $this->artisan('tasks:generate-daily')
        ->expectsOutput('Generated 2 task instance(s).')
        ->assertSuccessful();

    expect(TaskInstance::query()->where('user_id', $asiaUser->id)->firstOrFail()->scheduled_date->toDateString())
        ->toBe('2026-06-17')
        ->and(TaskInstance::query()->where('user_id', $usUser->id)->firstOrFail()->scheduled_date->toDateString())
        ->toBe('2026-06-16');
});
