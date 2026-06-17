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
        ->patch(route('categories.update', $category), [
            'name' => 'Fitness',
            'color' => '#16a34a',
            'icon' => '🏃',
        ])
        ->assertRedirect();

    expect($category->fresh()->name)->toBe('Fitness');

    $this->actingAs($user)
        ->delete(route('categories.destroy', $category))
        ->assertRedirect();

    expect(TaskCategory::query()->count())->toBe(0);
});

test('authenticated user can create update and archive tasks', function () {
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
            'category_public_id' => $category->public_id,
            'recurrence_type' => 'specific_days',
            'recurrence_days' => ['Mon', 'Wed', 'Fri'],
            'is_active' => true,
        ])
        ->assertRedirect(route('tasks.index'));

    $task = Task::query()->firstOrFail();

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
            'category_public_id' => $category->public_id,
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
        ->patch(route('instances.complete', $instance))
        ->assertRedirect();

    expect($instance->fresh()->points_awarded)->toBe(25)
        ->and($user->fresh()->total_points)->toBe(25);

    $this->actingAs($user)
        ->patch(route('instances.uncomplete', $instance))
        ->assertRedirect();

    expect($instance->fresh()->completed_at)->toBeNull()
        ->and($instance->fresh()->points_awarded)->toBeNull()
        ->and($user->fresh()->total_points)->toBe(0);
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
