<?php

declare(strict_types=1);

use App\Enums\RecurrenceType;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('user can create a daily recurring task', function () {
    actingAs($this->user)
        ->post(route('tasks.store'), [
            'title' => 'Daily Standup',
            'status' => 'pending',
            'priority' => 'medium',
            'due_at' => now()->addDay()->toDateTimeString(),
            'recurrence_type' => 'daily',
            'recurrence_interval' => 1,
        ])
        ->assertRedirect();

    assertDatabaseHas('tasks', [
        'user_id' => $this->user->id,
        'title' => 'Daily Standup',
        'recurrence_type' => 'daily',
        'recurrence_interval' => 1,
    ]);
});

test('user can create a weekly recurring task', function () {
    actingAs($this->user)
        ->post(route('tasks.store'), [
            'title' => 'Weekly Report',
            'status' => 'pending',
            'priority' => 'high',
            'due_at' => now()->addWeek()->toDateTimeString(),
            'recurrence_type' => 'weekly',
            'recurrence_interval' => 1,
        ])
        ->assertRedirect();

    assertDatabaseHas('tasks', [
        'user_id' => $this->user->id,
        'title' => 'Weekly Report',
        'recurrence_type' => 'weekly',
    ]);
});

test('user can create a monthly recurring task with custom interval', function () {
    actingAs($this->user)
        ->post(route('tasks.store'), [
            'title' => 'Quarterly Review',
            'status' => 'pending',
            'priority' => 'medium',
            'due_at' => now()->addMonths(3)->toDateTimeString(),
            'recurrence_type' => 'monthly',
            'recurrence_interval' => 3,
        ])
        ->assertRedirect();

    assertDatabaseHas('tasks', [
        'user_id' => $this->user->id,
        'title' => 'Quarterly Review',
        'recurrence_type' => 'monthly',
        'recurrence_interval' => 3,
    ]);
});

test('recurring task creates next occurrence when completed', function () {
    $task = Task::factory()
        ->forUser($this->user)
        ->create([
            'title' => 'Daily Task',
            'status' => TaskStatus::Pending,
            'due_at' => now(),
            'recurrence_type' => RecurrenceType::Daily,
            'recurrence_interval' => 1,
            'next_occurrence_at' => now()->addDay(),
        ]);

    actingAs($this->user)
        ->put(route('tasks.update', $task->id), [
            'title' => 'Daily Task',
            'status' => 'completed',
            'priority' => 'medium',
        ])
        ->assertRedirect();

    // A new task should have been created
    $this->assertDatabaseHas('tasks', [
        'user_id' => $this->user->id,
        'title' => 'Daily Task',
        'status' => 'pending',
        'parent_task_id' => $task->id,
    ]);
});

test('non-recurring task does not create next occurrence when completed', function () {
    $task = Task::factory()
        ->forUser($this->user)
        ->create([
            'title' => 'One-time Task',
            'status' => TaskStatus::Pending,
            'recurrence_type' => null,
        ]);

    actingAs($this->user)
        ->put(route('tasks.update', $task->id), [
            'title' => 'One-time Task',
            'status' => 'completed',
            'priority' => 'medium',
        ])
        ->assertRedirect();

    // No new task should have been created
    $this->assertDatabaseCount('tasks', 1);
});

test('user can update recurrence settings on existing task', function () {
    $task = Task::factory()
        ->forUser($this->user)
        ->create([
            'title' => 'Test Task',
            'recurrence_type' => null,
        ]);

    actingAs($this->user)
        ->put(route('tasks.update', $task->id), [
            'title' => 'Test Task',
            'status' => 'pending',
            'priority' => 'medium',
            'recurrence_type' => 'weekly',
            'recurrence_interval' => 2,
        ])
        ->assertRedirect();

    assertDatabaseHas('tasks', [
        'id' => $task->id,
        'recurrence_type' => 'weekly',
        'recurrence_interval' => 2,
    ]);
});

test('user can disable recurrence on a recurring task', function () {
    $task = Task::factory()
        ->forUser($this->user)
        ->create([
            'title' => 'Recurring Task',
            'recurrence_type' => RecurrenceType::Daily,
            'recurrence_interval' => 1,
            'next_occurrence_at' => now()->addDay(),
        ]);

    actingAs($this->user)
        ->put(route('tasks.update', $task->id), [
            'title' => 'Recurring Task',
            'status' => 'pending',
            'priority' => 'medium',
            'recurrence_type' => 'none',
        ])
        ->assertRedirect();

    assertDatabaseHas('tasks', [
        'id' => $task->id,
        'recurrence_type' => null,
        'next_occurrence_at' => null,
    ]);
});

test('edit page shows recurrence settings', function () {
    $task = Task::factory()
        ->forUser($this->user)
        ->create([
            'title' => 'Recurring Task',
            'recurrence_type' => RecurrenceType::Weekly,
            'recurrence_interval' => 1,
        ]);

    actingAs($this->user)
        ->get(route('tasks.edit', $task->id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Tasks/Edit')
            ->has('task.recurrence_type')
            ->has('recurrenceTypes')
        );
});
