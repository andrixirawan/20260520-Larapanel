<?php

use App\Models\User;

test('store task request requires recurrence days for specific day tasks', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('tasks.create'))
        ->post(route('tasks.store'), [
            'name' => 'Specific day task',
            'points' => 10,
            'recurrence_type' => 'specific_days',
            'recurrence_days' => [],
        ])
        ->assertRedirect(route('tasks.create'))
        ->assertSessionHasErrors('recurrence_days');
});

test('store task request requires recurrence start date for one time tasks', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('tasks.create'))
        ->post(route('tasks.store'), [
            'name' => 'One time task',
            'points' => 10,
            'recurrence_type' => 'one_time',
        ])
        ->assertRedirect(route('tasks.create'))
        ->assertSessionHasErrors('recurrence_starts_at');
});

test('store task request requires recurrence end date for x days tasks', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('tasks.create'))
        ->post(route('tasks.store'), [
            'name' => 'X days task',
            'points' => 10,
            'recurrence_type' => 'x_days',
        ])
        ->assertRedirect(route('tasks.create'))
        ->assertSessionHasErrors('recurrence_ends_at');
});

test('update task request rejects categories owned by another user', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherCategory = $otherUser->categories()->create(['name' => 'Private']);
    $task = $owner->tasks()->create([
        'name' => 'Task',
        'points' => 10,
        'recurrence_type' => 'daily',
        'is_active' => true,
    ]);

    $this->actingAs($owner)
        ->from(route('tasks.edit', $task))
        ->patch(route('tasks.update', $task), [
            'name' => 'Task',
            'points' => 10,
            'recurrence_type' => 'daily',
            'category_public_id' => $otherCategory->public_id,
        ])
        ->assertRedirect(route('tasks.edit', $task))
        ->assertSessionHasErrors('category_public_id');

    expect($task->fresh()->category_id)->toBeNull();
});

test('task resource exposes recurrence summary and category through tasks index', function () {
    $user = User::factory()->create();
    $category = $user->categories()->create([
        'name' => 'Health',
        'color' => '#22c55e',
        'icon' => '💪',
    ]);

    $user->tasks()->create([
        'name' => 'Morning run',
        'points' => 20,
        'recurrence_type' => 'daily',
        'category_id' => $category->id,
        'icon' => '🏃',
        'color' => '#16a34a',
        'is_active' => true,
    ]);

    $this->withoutVite();

    $this->actingAs($user)
        ->get(route('tasks.index'))
        ->assertInertia(fn ($page) => $page
            ->component('daily-quest/tasks/index')
            ->where('tasks.0.recurrence_summary', 'Daily')
            ->where('tasks.0.category.name', 'Health')
            ->where('tasks.0.points', 20)
        );
});
