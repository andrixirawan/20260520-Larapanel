<?php

use App\Actions\Mobile\CreateMobileAuthToken;
use App\Models\DailyQuest\Task;
use App\Models\DailyQuest\TaskCategory;
use App\Models\DailyQuest\TaskInstance;
use App\Models\DailyQuest\UserDailyStat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function mobileDailyQuestTokenFor(User $user): string
{
    [$token] = app(CreateMobileAuthToken::class)->handle(
        $user,
        request()->merge(['device_name' => 'Daily Quest Mobile']),
    );

    return $token;
}

function makeMobileDailyQuestTask(User $user, array $attributes = []): Task
{
    return Task::query()->create(array_merge([
        'user_id' => $user->id,
        'name' => 'Daily task',
        'points' => 10,
        'recurrence_type' => 'daily',
        'is_active' => true,
    ], $attributes));
}

test('mobile daily quest endpoints require authentication', function () {
    $this->getJson(route('api.mobile.daily-quest.today'))
        ->assertUnauthorized();
});

test('mobile daily quest dashboard and today endpoints return mobile payload', function () {
    Carbon::setTestNow('2026-06-18 08:00:00');

    $user = User::factory()->create([
        'timezone' => 'Asia/Jakarta',
        'total_points' => 55,
        'current_streak' => 4,
        'longest_streak' => 7,
    ]);
    $token = mobileDailyQuestTokenFor($user);
    $task = makeMobileDailyQuestTask($user, ['name' => 'Morning run', 'points' => 25]);

    TaskInstance::query()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'scheduled_date' => '2026-06-18',
        'completed_at' => '2026-06-18 06:00:00',
        'points_awarded' => 25,
    ]);

    $this->withToken($token)
        ->getJson(route('api.mobile.daily-quest.dashboard'))
        ->assertOk()
        ->assertJsonPath('data.stats.streak.current', 4)
        ->assertJsonPath('data.stats.points.total', 55)
        ->assertJsonPath('data.date_range.today', '2026-06-18');

    $this->withToken($token)
        ->getJson(route('api.mobile.daily-quest.today'))
        ->assertOk()
        ->assertJsonPath('data.date', '2026-06-18')
        ->assertJsonPath('data.stats.completed_tasks', 1)
        ->assertJsonPath('data.instances.0.task.name', 'Morning run')
        ->assertJsonPath('data.all_completed', true);
});

test('mobile daily quest tasks and categories can be managed', function () {
    Carbon::setTestNow('2026-06-18 08:00:00');

    $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
    $token = mobileDailyQuestTokenFor($user);

    $categoryResponse = $this->withToken($token)
        ->postJson(route('api.mobile.daily-quest.categories.store'), [
            'name' => 'Health',
            'color' => '#22c55e',
            'icon' => 'H',
        ])
        ->assertCreated()
        ->assertJsonPath('message', 'Category created.');

    $categoryId = $categoryResponse->json('data.id');

    $taskResponse = $this->withToken($token)
        ->postJson(route('api.mobile.daily-quest.tasks.store'), [
            'name' => 'Drink water',
            'description' => '2 liters',
            'icon' => 'W',
            'color' => '#0ea5e9',
            'points' => 20,
            'category_id' => $categoryId,
            'recurrence_type' => 'daily',
        ])
        ->assertCreated()
        ->assertJsonPath('message', 'Task created.')
        ->assertJsonPath('data.category.id', $categoryId);

    $taskId = $taskResponse->json('data.id');

    $this->withToken($token)
        ->getJson(route('api.mobile.daily-quest.tasks.index', ['status' => 'active']))
        ->assertOk()
        ->assertJsonPath('filters.status', 'active')
        ->assertJsonPath('data.0.name', 'Drink water')
        ->assertJsonCount(1, 'categories');

    $this->withToken($token)
        ->getJson(route('api.mobile.daily-quest.tasks.show', $taskId))
        ->assertOk()
        ->assertJsonPath('data.id', $taskId)
        ->assertJsonCount(5, 'recurrence_types');

    $this->withToken($token)
        ->patchJson(route('api.mobile.daily-quest.tasks.update', $taskId), [
            'name' => 'Drink more water',
            'description' => '3 liters',
            'icon' => 'D',
            'color' => '#0284c7',
            'points' => 25,
            'category_id' => $categoryId,
            'recurrence_type' => 'specific_days',
            'recurrence_days' => ['Mon', 'Wed', 'Fri'],
        ])
        ->assertOk()
        ->assertJsonPath('message', 'Task updated.')
        ->assertJsonPath('data.recurrence_type', 'specific_days');

    $this->withToken($token)
        ->postJson(route('api.mobile.daily-quest.tasks.pause', $taskId))
        ->assertOk()
        ->assertJsonPath('message', 'Task paused.')
        ->assertJsonPath('data.is_active', false);

    $duplicate = $this->withToken($token)
        ->postJson(route('api.mobile.daily-quest.tasks.duplicate', $taskId))
        ->assertCreated()
        ->assertJsonPath('message', 'Task duplicated.');

    $duplicateId = $duplicate->json('data.id');

    $this->withToken($token)
        ->deleteJson(route('api.mobile.daily-quest.tasks.destroy', $taskId))
        ->assertOk()
        ->assertJsonPath('message', 'Task archived.');

    $this->withToken($token)
        ->patchJson(route('api.mobile.daily-quest.categories.update', $categoryId), [
            'name' => 'Fitness',
            'color' => '#16a34a',
            'icon' => 'F',
        ])
        ->assertOk()
        ->assertJsonPath('message', 'Category updated.')
        ->assertJsonPath('data.name', 'Fitness');

    $this->withToken($token)
        ->deleteJson(route('api.mobile.daily-quest.categories.destroy', $categoryId))
        ->assertOk()
        ->assertJsonPath('message', 'Category deleted.');

    expect(Task::query()->whereKey($duplicateId)->exists())->toBeTrue()
        ->and(Task::withTrashed()->whereKey($taskId)->firstOrFail()->trashed())->toBeTrue()
        ->and(TaskCategory::query()->whereKey($categoryId)->exists())->toBeFalse();
});

test('mobile daily quest instances can be completed uncompleted and noted for today', function () {
    Carbon::setTestNow('2026-06-18 08:00:00');

    $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
    $token = mobileDailyQuestTokenFor($user);
    $task = makeMobileDailyQuestTask($user, ['points' => 30]);

    TaskInstance::query()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'scheduled_date' => '2026-06-18',
    ]);

    $this->withToken($token)
        ->postJson(route('api.mobile.daily-quest.instances.complete', 'stale-instance-id'), [
            'task_id' => $task->id,
        ])
        ->assertOk()
        ->assertJsonPath('message', 'Task completed.')
        ->assertJsonPath('data.points_awarded', 30);

    $instance = TaskInstance::query()
        ->where('task_id', $task->id)
        ->whereDate('scheduled_date', '2026-06-18')
        ->firstOrFail();

    $this->withToken($token)
        ->patchJson(route('api.mobile.daily-quest.instances.notes.update', $instance->id), [
            'notes' => 'Completed before breakfast.',
        ])
        ->assertOk()
        ->assertJsonPath('message', 'Task notes updated.')
        ->assertJsonPath('data.notes', 'Completed before breakfast.');

    $this->withToken($token)
        ->postJson(route('api.mobile.daily-quest.instances.uncomplete', $instance->id))
        ->assertOk()
        ->assertJsonPath('message', 'Task marked as incomplete.')
        ->assertJsonPath('data.completed_at', null)
        ->assertJsonPath('data.points_awarded', null);
});

test('mobile daily quest history and profile endpoints expose screen data', function () {
    Carbon::setTestNow('2026-06-18 08:00:00');

    $user = User::factory()->create([
        'name' => 'Quest User',
        'timezone' => 'Asia/Jakarta',
        'total_points' => 80,
        'current_streak' => 3,
        'longest_streak' => 6,
    ]);
    $token = mobileDailyQuestTokenFor($user);
    $category = $user->categories()->create([
        'name' => 'Study',
        'color' => '#3b82f6',
        'icon' => 'S',
    ]);
    $task = makeMobileDailyQuestTask($user, [
        'name' => 'Read book',
        'category_id' => $category->id,
    ]);

    TaskInstance::query()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'scheduled_date' => '2026-06-17',
        'completed_at' => '2026-06-17 06:00:00',
        'points_awarded' => 10,
        'notes' => 'Chapter 4',
    ]);

    UserDailyStat::query()->create([
        'user_id' => $user->id,
        'date' => '2026-06-17',
        'total_tasks' => 1,
        'completed_tasks' => 1,
        'points_earned' => 10,
    ]);

    $this->withToken($token)
        ->getJson(route('api.mobile.daily-quest.history.index', [
            'month' => '2026-06',
            'category_id' => $category->id,
        ]))
        ->assertOk()
        ->assertJsonPath('month', '2026-06')
        ->assertJsonPath('filters.category_id', $category->id)
        ->assertJsonPath('calendar.0.date', '2026-06-17')
        ->assertJsonPath('data.0.summary.points_earned', 10)
        ->assertJsonPath('data.0.instances.0.notes', 'Chapter 4');

    $this->withToken($token)
        ->getJson(route('api.mobile.daily-quest.history.show', ['date' => '2026-06-17']))
        ->assertOk()
        ->assertJsonPath('data.date', '2026-06-17')
        ->assertJsonPath('data.summary.completed_tasks', 1);

    $this->withToken($token)
        ->getJson(route('api.mobile.daily-quest.profile.show'))
        ->assertOk()
        ->assertJsonPath('data.user.name', 'Quest User')
        ->assertJsonPath('data.stats.points.total', 80)
        ->assertJsonPath('data.stats.categories.total', 1);

    $this->withToken($token)
        ->patchJson(route('api.mobile.daily-quest.profile.display-name.update'), [
            'name' => 'Updated Quest User',
        ])
        ->assertOk()
        ->assertJsonPath('message', 'Display name updated.')
        ->assertJsonPath('data.name', 'Updated Quest User');

    expect($user->fresh()->name)->toBe('Updated Quest User');
});
