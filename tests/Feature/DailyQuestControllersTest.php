<?php

use App\Models\DailyQuest\Task;
use App\Models\DailyQuest\TaskCategory;
use App\Models\DailyQuest\TaskInstance;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

function makeDailyQuestTask(User $user, array $attributes = []): Task
{
    return Task::query()->create(array_merge([
        'user_id' => $user->id,
        'name' => 'Daily Task',
        'points' => 10,
        'recurrence_type' => 'daily',
        'is_active' => true,
    ], $attributes));
}

test('authenticated user can manage task categories', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('categories.store'), [
            'name' => 'Health',
            'color' => '#22c55e',
            'icon' => '💪',
        ])
        ->assertRedirect();

    $category = TaskCategory::query()->firstOrFail();

    $this->actingAs($user)
        ->get(route('categories.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('daily-quest/categories/index')
            ->where('categories.0.name', 'Health')
        );

    $this->actingAs($user)
        ->patch("/categories/{$category->id}", [
            'name' => 'Fitness',
            'color' => '#16a34a',
            'icon' => '🏃',
        ])
        ->assertRedirect();

    expect($category->fresh()->name)->toBe('Fitness');

    $this->actingAs($user)
        ->delete("/categories/{$category->id}")
        ->assertRedirect();

    expect(TaskCategory::query()->count())->toBe(0);
});

test('authenticated user can create update and archive tasks', function () {
    Carbon::setTestNow('2026-06-17 08:00:00');

    $user = User::factory()->create();
    $category = $user->categories()->create([
        'name' => 'Study',
        'color' => '#3b82f6',
        'icon' => '📚',
    ]);

    $this->actingAs($user)
        ->post(route('tasks.store'), [
            'name' => 'Read 10 pages',
            'description' => 'Keep the streak going.',
            'icon' => '📖',
            'color' => '#2563eb',
            'points' => 15,
            'category_id' => $category->id,
            'recurrence_type' => 'specific_days',
            'recurrence_days' => ['Mon', 'Wed', 'Fri'],
            'is_active' => true,
        ])
        ->assertRedirect(route('tasks.index'));

    $task = Task::query()->firstOrFail();

    $this->assertDatabaseHas('task_instances', [
        'task_id' => $task->id,
        'user_id' => $user->id,
        'scheduled_date' => '2026-06-17 00:00:00',
    ]);

    $this->actingAs($user)
        ->get(route('tasks.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('daily-quest/tasks/index')
            ->where('tasks.0.name', 'Read 10 pages')
        );

    $this->actingAs($user)
        ->patch(route('tasks.update', $task), [
            'name' => 'Read 20 pages',
            'description' => 'Updated.',
            'icon' => '📘',
            'color' => '#1d4ed8',
            'points' => 20,
            'category_id' => $category->id,
            'recurrence_type' => 'daily',
            'recurrence_days' => [],
            'is_active' => true,
        ])
        ->assertRedirect(route('tasks.index'));

    expect($task->fresh()->name)->toBe('Read 20 pages');

    $this->actingAs($user)
        ->delete(route('tasks.destroy', $task))
        ->assertRedirect(route('tasks.index'));

    expect($task->fresh()->trashed())->toBeTrue();
});

test('authenticated user can pause and duplicate tasks', function () {
    $user = User::factory()->create();
    $category = $user->categories()->create([
        'name' => 'Health',
        'color' => '#22c55e',
        'icon' => '💪',
    ]);
    $task = makeDailyQuestTask($user, [
        'category_id' => $category->id,
        'name' => 'Drink water',
        'description' => 'Two liters.',
        'icon' => '💧',
        'color' => '#0ea5e9',
        'points' => 12,
        'recurrence_type' => 'specific_days',
        'recurrence_days' => ['Mon', 'Wed', 'Fri'],
    ]);

    $this->actingAs($user)
        ->post(route('tasks.pause.post', $task), [
            'redirect_to' => '/tasks?status=active',
        ])
        ->assertRedirect('/tasks?status=active');

    expect($task->fresh()->is_active)->toBeFalse();

    $this->actingAs($user)
        ->post(route('tasks.duplicate', $task), [
            'redirect_to' => '/tasks?status=paused',
        ])
        ->assertRedirect('/tasks?status=paused');

    expect(Task::query()->count())->toBe(2);

    $duplicate = Task::query()
        ->where('id', '!=', $task->id)
        ->firstOrFail();

    expect($duplicate->name)->toBe('Drink water (Copy)')
        ->and($duplicate->description)->toBe('Two liters.')
        ->and($duplicate->icon)->toBe('💧')
        ->and($duplicate->color)->toBe('#0ea5e9')
        ->and($duplicate->points)->toBe(12)
        ->and($duplicate->recurrence_days)->toBe(['Mon', 'Wed', 'Fri'])
        ->and($duplicate->category_id)->toBe($category->id)
        ->and($duplicate->is_active)->toBeTrue();
});

test('today page returns todays instances and completion stats', function () {
    Carbon::setTestNow('2026-06-17 08:00:00');

    $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
    $task = makeDailyQuestTask($user, ['name' => 'Morning run']);

    TaskInstance::query()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'scheduled_date' => '2026-06-17',
        'completed_at' => '2026-06-17 07:00:00',
        'points_awarded' => 10,
    ]);

    $this->actingAs($user)
        ->get(route('today'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('daily-quest/today/index')
            ->where('date', '2026-06-17')
            ->where('stats.total_tasks', 1)
            ->where('stats.completed_tasks', 1)
            ->where('instances.0.task_id', $task->id)
            ->where('instances.0.task.name', 'Morning run')
        );
});

test('task instances can be completed and uncompleted for today only', function () {
    Carbon::setTestNow('2026-06-17 08:00:00');
    Mail::fake();

    $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
    $task = makeDailyQuestTask($user, ['points' => 25]);
    $instance = TaskInstance::query()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'scheduled_date' => '2026-06-17',
    ]);

    $this->actingAs($user)
        ->post(route('instances.complete.post', $instance))
        ->assertRedirect();

    expect($instance->fresh()->points_awarded)->toBe(25)
        ->and($user->fresh()->total_points)->toBe(25);

    $this->actingAs($user)
        ->post(route('instances.uncomplete.post', $instance))
        ->assertRedirect();

    expect($instance->fresh()->completed_at)->toBeNull()
        ->and($instance->fresh()->points_awarded)->toBeNull()
        ->and($user->fresh()->total_points)->toBe(0);
});

test('today task instance completion also accepts the task id for todays instance', function () {
    Carbon::setTestNow('2026-06-17 08:00:00');

    $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
    $task = makeDailyQuestTask($user, ['points' => 30]);

    TaskInstance::query()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'scheduled_date' => '2026-06-17',
    ]);

    $this->actingAs($user)
        ->post(route('instances.complete.post', 'not-the-instance-id'), [
            'task_id' => $task->id,
        ])
        ->assertRedirect();

    $instance = TaskInstance::query()
        ->where('task_id', $task->id)
        ->whereDate('scheduled_date', '2026-06-17')
        ->firstOrFail();

    expect($instance->completed_at)->not->toBeNull()
        ->and($instance->points_awarded)->toBe(30);
});

test('today task instance completion resolves stale instance ids through the submitted task id', function () {
    Carbon::setTestNow('2026-06-18 03:30:00');

    $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
    $task = makeDailyQuestTask($user, ['points' => 35]);
    $staleInstance = TaskInstance::query()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'scheduled_date' => '2026-06-17',
    ]);
    $todayInstance = TaskInstance::query()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'scheduled_date' => '2026-06-18',
    ]);

    $this->actingAs($user)
        ->post(route('instances.complete.post', $staleInstance), [
            'task_id' => $task->id,
        ])
        ->assertRedirect();

    expect($staleInstance->fresh()->completed_at)->toBeNull()
        ->and($todayInstance->fresh()->completed_at)->not->toBeNull()
        ->and($todayInstance->fresh()->points_awarded)->toBe(35);
});

test('today task instance completion generates todays instance when the submitted task id is valid', function () {
    Carbon::setTestNow('2026-06-18 03:30:00');

    $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
    $task = makeDailyQuestTask($user, ['points' => 40]);

    $this->actingAs($user)
        ->post(route('instances.complete.post', 'stale-instance-id'), [
            'task_id' => $task->id,
        ])
        ->assertRedirect();

    $instance = TaskInstance::query()
        ->where('task_id', $task->id)
        ->whereDate('scheduled_date', '2026-06-18')
        ->firstOrFail();

    expect($instance->completed_at)->not->toBeNull()
        ->and($instance->points_awarded)->toBe(40);
});

test('browser post endpoints mutate daily quest resources without method spoofing', function () {
    Carbon::setTestNow('2026-06-17 08:00:00');

    $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
    $category = $user->categories()->create([
        'name' => 'Focus',
        'color' => '#0f766e',
        'icon' => 'F',
    ]);
    $deleteCategory = $user->categories()->create([
        'name' => 'Delete me',
        'color' => '#dc2626',
        'icon' => 'D',
    ]);
    $task = makeDailyQuestTask($user, [
        'category_id' => $category->id,
        'name' => 'Deep work',
        'points' => 40,
    ]);
    $instance = TaskInstance::query()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'scheduled_date' => '2026-06-17',
    ]);

    $this->actingAs($user)
        ->post(route('instances.complete.post', $instance), [
            'task_id' => $task->id,
        ])
        ->assertRedirect();

    expect($instance->fresh()->completed_at)->not->toBeNull();

    $this->actingAs($user)
        ->post(route('tasks.pause.post', $task), [
            'redirect_to' => '/today',
        ])
        ->assertRedirect('/today');

    expect($task->fresh()->is_active)->toBeFalse();

    $this->actingAs($user)
        ->post(route('tasks.update.post', $task), [
            'name' => 'Deep work updated',
            'description' => 'No meetings.',
            'icon' => 'W',
            'color' => '#0ea5e9',
            'points' => 45,
            'category_id' => $category->id,
            'recurrence_type' => 'daily',
            'recurrence_days' => [],
            'is_active' => true,
            'redirect_to' => '/tasks',
        ])
        ->assertRedirect('/tasks');

    expect($task->fresh()->name)->toBe('Deep work updated');

    $this->actingAs($user)
        ->post(route('categories.update.post', $category), [
            'name' => 'Focus Updated',
            'color' => '#14b8a6',
            'icon' => 'U',
        ])
        ->assertRedirect();

    expect($category->fresh()->name)->toBe('Focus Updated');

    $this->actingAs($user)
        ->post(route('categories.destroy.post', $deleteCategory))
        ->assertRedirect();

    expect(TaskCategory::query()->whereKey($deleteCategory->id)->exists())->toBeFalse();

    $this->actingAs($user)
        ->post(route('tasks.destroy.post', $task), [
            'redirect_to' => '/tasks?status=active',
        ])
        ->assertRedirect('/tasks?status=active');

    expect($task->fresh()->trashed())->toBeTrue();
});

test('history and dashboard pages expose daily quest summaries', function () {
    Carbon::setTestNow('2026-06-17 08:00:00');

    $user = User::factory()->create([
        'timezone' => 'Asia/Jakarta',
        'total_points' => 35,
        'current_streak' => 2,
        'longest_streak' => 4,
    ]);
    $category = $user->categories()->create(['name' => 'Health']);
    $task = makeDailyQuestTask($user, ['category_id' => $category->id, 'name' => 'Stretch']);

    TaskInstance::query()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'scheduled_date' => '2026-06-16',
        'completed_at' => '2026-06-16 06:00:00',
        'points_awarded' => 15,
    ]);
    TaskInstance::query()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'scheduled_date' => '2026-06-17',
        'completed_at' => '2026-06-17 06:00:00',
        'points_awarded' => 20,
    ]);

    $this->actingAs($user)
        ->get(route('history'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('daily-quest/history/index')
            ->where('days.0.summary.date', '2026-06-17')
        );

    $this->actingAs($user)
        ->get(route('history.show', ['date' => '2026-06-16']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('daily-quest/history/show')
            ->where('date', '2026-06-16')
            ->where('summary.points_earned', 15)
        );

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('stats.streak.current', 2)
            ->where('stats.points.total', 35)
        );
});

test('daily quest profile page renders and display name can be updated', function () {
    Carbon::setTestNow('2026-06-17 08:00:00');

    $user = User::factory()->create([
        'name' => 'Old Name',
        'timezone' => 'Asia/Jakarta',
        'total_points' => 80,
        'current_streak' => 3,
        'longest_streak' => 5,
    ]);
    $task = makeDailyQuestTask($user, ['name' => 'Walk']);

    TaskInstance::query()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'scheduled_date' => '2026-06-17',
        'completed_at' => '2026-06-17 06:30:00',
        'points_awarded' => 10,
    ]);

    $this->actingAs($user)
        ->get(route('daily-quest.profile'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('daily-quest/profile/index')
            ->where('stats.streak.current', 3)
            ->where('stats.points.total', 80)
        );

    $this->actingAs($user)
        ->post(route('daily-quest.profile.display-name.post'), [
            'name' => 'New Name',
        ])
        ->assertRedirect(route('daily-quest.profile'));

    expect($user->fresh()->name)->toBe('New Name');
});
