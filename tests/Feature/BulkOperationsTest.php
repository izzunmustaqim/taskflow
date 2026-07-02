<?php

declare(strict_types=1);

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\assertSoftDeleted;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('user can bulk delete their own tasks', function () {
    $tasks = Task::factory()->count(3)->forUser($this->user)->create();

    actingAs($this->user)
        ->post(route('tasks.bulk-delete'), [
            'ids' => $tasks->pluck('id')->toArray(),
        ])
        ->assertRedirect()
        ->assertSessionHas('success', '3 task(s) moved to trash.');

    foreach ($tasks as $task) {
        assertSoftDeleted('tasks', ['id' => $task->id]);
    }
});

test('user cannot bulk delete tasks belonging to another user', function () {
    $otherUser = User::factory()->create();
    $otherTasks = Task::factory()->count(3)->forUser($otherUser)->create();

    actingAs($this->user)
        ->post(route('tasks.bulk-delete'), [
            'ids' => $otherTasks->pluck('id')->toArray(),
        ])
        ->assertRedirect();

    foreach ($otherTasks as $task) {
        assertDatabaseHas('tasks', ['id' => $task->id, 'deleted_at' => null]);
    }
});

test('bulk delete only affects authenticated user tasks', function () {
    $myTasks = Task::factory()->count(2)->forUser($this->user)->create();
    $otherUser = User::factory()->create();
    $otherTasks = Task::factory()->count(2)->forUser($otherUser)->create();

    $mixedIds = array_merge(
        $myTasks->pluck('id')->toArray(),
        $otherTasks->pluck('id')->toArray()
    );

    actingAs($this->user)
        ->post(route('tasks.bulk-delete'), ['ids' => $mixedIds])
        ->assertRedirect();

    foreach ($myTasks as $task) {
        assertSoftDeleted('tasks', ['id' => $task->id]);
    }
    foreach ($otherTasks as $task) {
        assertDatabaseHas('tasks', ['id' => $task->id, 'deleted_at' => null]);
    }
});

test('user can bulk update status of their own tasks', function () {
    $tasks = Task::factory()->count(3)->forUser($this->user)->create(['status' => TaskStatus::Pending]);

    actingAs($this->user)
        ->post(route('tasks.bulk-status'), [
            'ids' => $tasks->pluck('id')->toArray(),
            'status' => TaskStatus::Completed->value,
        ])
        ->assertRedirect()
        ->assertSessionHas('success', '3 task(s) updated to Completed.');

    foreach ($tasks as $task) {
        expect($task->fresh()->status)->toBe(TaskStatus::Completed);
    }
});

test('user cannot bulk update status of another user tasks', function () {
    $otherUser = User::factory()->create();
    $otherTasks = Task::factory()->count(2)->forUser($otherUser)->create(['status' => TaskStatus::Pending]);

    actingAs($this->user)
        ->post(route('tasks.bulk-status'), [
            'ids' => $otherTasks->pluck('id')->toArray(),
            'status' => TaskStatus::Completed->value,
        ])
        ->assertRedirect();

    foreach ($otherTasks as $task) {
        expect($task->fresh()->status)->toBe(TaskStatus::Pending);
    }
});

test('user can bulk restore their own trashed tasks', function () {
    $tasks = Task::factory()->count(3)->forUser($this->user)->create(['deleted_at' => now()]);

    actingAs($this->user)
        ->post(route('tasks.bulk-restore'), ['ids' => $tasks->pluck('id')->toArray()])
        ->assertRedirect()
        ->assertSessionHas('success', '3 task(s) restored successfully.');

    foreach ($tasks as $task) {
        assertDatabaseHas('tasks', ['id' => $task->id, 'deleted_at' => null]);
    }
});

test('user cannot bulk restore another user tasks', function () {
    $otherUser = User::factory()->create();
    $otherTasks = Task::factory()->count(2)->forUser($otherUser)->create(['deleted_at' => now()]);

    actingAs($this->user)
        ->post(route('tasks.bulk-restore'), ['ids' => $otherTasks->pluck('id')->toArray()])
        ->assertRedirect();

    foreach ($otherTasks as $task) {
        assertSoftDeleted('tasks', ['id' => $task->id]);
    }
});

test('user can bulk force delete their own trashed tasks', function () {
    $tasks = Task::factory()->count(3)->forUser($this->user)->create(['deleted_at' => now()]);

    actingAs($this->user)
        ->post(route('tasks.bulk-force-delete'), ['ids' => $tasks->pluck('id')->toArray()])
        ->assertRedirect()
        ->assertSessionHas('success', '3 task(s) permanently deleted.');

    foreach ($tasks as $task) {
        assertDatabaseMissing('tasks', ['id' => $task->id]);
    }
});

test('user cannot bulk force delete another user tasks', function () {
    $otherUser = User::factory()->create();
    $otherTasks = Task::factory()->count(2)->forUser($otherUser)->create(['deleted_at' => now()]);

    actingAs($this->user)
        ->post(route('tasks.bulk-force-delete'), ['ids' => $otherTasks->pluck('id')->toArray()])
        ->assertRedirect();

    foreach ($otherTasks as $task) {
        assertDatabaseHas('tasks', ['id' => $task->id]);
    }
});

test('unauthenticated user cannot access bulk endpoints', function () {
    $tasks = Task::factory()->count(2)->create();

    $this->post(route('tasks.bulk-delete'), ['ids' => $tasks->pluck('id')->toArray()])->assertRedirect('/login');
    $this->post(route('tasks.bulk-status'), ['ids' => $tasks->pluck('id')->toArray(), 'status' => TaskStatus::Completed->value])->assertRedirect('/login');
});

test('bulk delete requires at least one id', function () {
    actingAs($this->user)
        ->post(route('tasks.bulk-delete'), ['ids' => []])
        ->assertSessionHasErrors('ids');
});

test('bulk status requires valid status', function () {
    $tasks = Task::factory()->count(2)->forUser($this->user)->create();

    actingAs($this->user)
        ->post(route('tasks.bulk-status'), ['ids' => $tasks->pluck('id')->toArray(), 'status' => 'invalid'])
        ->assertSessionHasErrors('status');
});

// ─── Reorder Tests ────────────────────────────────────────────

test('user can reorder their own tasks', function () {
    $tasks = Task::factory()->count(3)->forUser($this->user)->create();
    $reversedIds = $tasks->pluck('id')->reverse()->values()->toArray();

    actingAs($this->user)
        ->postJson(route('tasks.reorder'), ['ids' => $reversedIds])
        ->assertOk()
        ->assertJson(['success' => true]);

    foreach ($reversedIds as $position => $id) {
        assertDatabaseHas('tasks', ['id' => $id, 'sort_order' => $position]);
    }
});

test('user cannot reorder tasks belonging to another user', function () {
    $otherUser = User::factory()->create();
    $otherTasks = Task::factory()->count(3)->forUser($otherUser)->create();
    $reversedIds = $otherTasks->pluck('id')->reverse()->values()->toArray();

    actingAs($this->user)
        ->postJson(route('tasks.reorder'), ['ids' => $reversedIds])
        ->assertOk();

    // sort_order should remain at default (0) for other user's tasks
    foreach ($otherTasks as $task) {
        expect($task->fresh()->sort_order)->toBe(0);
    }
});

test('reorder requires at least one id', function () {
    actingAs($this->user)
        ->postJson(route('tasks.reorder'), ['ids' => []])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('ids');
});

test('reorder only updates tasks belonging to the authenticated user', function () {
    $myTasks = Task::factory()->count(2)->forUser($this->user)->create();
    $otherUser = User::factory()->create();
    $otherTasks = Task::factory()->count(2)->forUser($otherUser)->create();

    // Mix of my tasks and other user's tasks
    $mixedIds = array_merge(
        $myTasks->pluck('id')->toArray(),
        $otherTasks->pluck('id')->toArray()
    );

    actingAs($this->user)
        ->postJson(route('tasks.reorder'), ['ids' => $mixedIds])
        ->assertOk();

    // My tasks should be reordered
    assertDatabaseHas('tasks', ['id' => $myTasks[0]->id, 'sort_order' => 0]);
    assertDatabaseHas('tasks', ['id' => $myTasks[1]->id, 'sort_order' => 1]);

    // Other user's tasks should remain at default
    foreach ($otherTasks as $task) {
        expect($task->fresh()->sort_order)->toBe(0);
    }
});

test('unauthenticated user cannot reorder tasks', function () {
    $tasks = Task::factory()->count(2)->create();

    $this->post(route('tasks.reorder'), ['ids' => $tasks->pluck('id')->toArray()])
        ->assertRedirect('/login');
});
