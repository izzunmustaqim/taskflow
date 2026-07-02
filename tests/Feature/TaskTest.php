<?php

declare(strict_types=1);

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Category;
use App\Models\Task;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\assertSoftDeleted;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('user can view their dashboard stats and recent tasks', function () {
    Task::factory()->count(2)->forUser($this->user)->create();
    
    actingAs($this->user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('stats.total')
            ->has('recentTasks', 2)
        );
});

test('user can view their task list', function () {
    Task::factory()->count(3)->forUser($this->user)->create();

    actingAs($this->user)
        ->get(route('tasks.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Tasks/Index')
            ->has('tasks.data', 3)
        );
});

test('user cannot view another users task', function () {
    $otherUser = User::factory()->create();
    $task = Task::factory()->forUser($otherUser)->create();

    actingAs($this->user)
        ->get(route('tasks.edit', $task))
        ->assertForbidden();
});

test('user can create a task', function () {
    actingAs($this->user)
        ->post(route('tasks.store'), [
            'title' => 'Buy Groceries',
            'description' => 'Milk, eggs, bread',
            'status' => TaskStatus::Pending->value,
            'priority' => TaskPriority::Medium->value,
        ])
        ->assertRedirect(route('tasks.edit', Task::first()))
        ->assertSessionHas('success', 'Task created successfully. You can now add attachments.');

    assertDatabaseHas('tasks', [
        'user_id' => $this->user->id,
        'title' => 'Buy Groceries',
    ]);
});

test('user cannot assign a category belonging to another user', function () {
    $otherUser = User::factory()->create();
    $otherCategory = Category::factory()->forUser($otherUser)->create();

    actingAs($this->user)
        ->post(route('tasks.store'), [
            'title' => 'Hacked Task',
            'status' => TaskStatus::Pending->value,
            'priority' => TaskPriority::Medium->value,
            'category_id' => $otherCategory->id,
        ])
        ->assertSessionHasErrors(['category_id']);

    assertDatabaseCount('tasks', 0);
});

test('user can update their task', function () {
    $task = Task::factory()->forUser($this->user)->create([
        'title' => 'Old Title',
    ]);

    actingAs($this->user)
        ->put(route('tasks.update', $task), [
            'title' => 'New Title',
            'status' => TaskStatus::InProgress->value,
            'priority' => TaskPriority::High->value,
        ])
        ->assertRedirect();

    expect($task->fresh()->title)->toBe('New Title');
});

test('user cannot update another users task', function () {
    $otherUser = User::factory()->create();
    $task = Task::factory()->forUser($otherUser)->create();

    actingAs($this->user)
        ->put(route('tasks.update', $task), [
            'title' => 'Hacked Title',
        ])
        ->assertForbidden();
});

test('user can soft delete a task', function () {
    $task = Task::factory()->forUser($this->user)->create();

    actingAs($this->user)
        ->delete(route('tasks.destroy', $task))
        ->assertRedirect();

    assertSoftDeleted('tasks', ['id' => $task->id]);
});

test('user can restore a soft deleted task', function () {
    $task = Task::factory()->forUser($this->user)->create(['deleted_at' => now()]);

    actingAs($this->user)
        ->post(route('tasks.restore', $task))
        ->assertRedirect();

    assertDatabaseHas('tasks', [
        'id' => $task->id,
        'deleted_at' => null,
    ]);
});

test('user can permanently delete a task', function () {
    $task = Task::factory()->forUser($this->user)->create(['deleted_at' => now()]);

    actingAs($this->user)
        ->delete(route('tasks.force-destroy', $task))
        ->assertRedirect();

    assertDatabaseMissing('tasks', ['id' => $task->id]);
});

test('user cannot force delete another users task', function () {
    $otherUser = User::factory()->create();
    $task = Task::factory()->forUser($otherUser)->create(['deleted_at' => now()]);

    actingAs($this->user)
        ->delete(route('tasks.force-destroy', $task))
        ->assertForbidden();
});
