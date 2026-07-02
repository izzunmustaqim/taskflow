<?php

declare(strict_types=1);

use App\Models\Label;
use App\Models\Task;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('authenticated user can view their labels', function () {
    $labels = Label::factory()->count(3)->forUser($this->user)->create();

    actingAs($this->user)
        ->get(route('labels.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Labels/Index')->has('labels', 3));
});

test('authenticated user can create a label', function () {
    actingAs($this->user)
        ->post(route('labels.store'), [
            'name' => 'Bug',
            'color' => '#ef4444',
        ])
        ->assertRedirect(route('labels.index'));

    assertDatabaseHas('labels', [
        'user_id' => $this->user->id,
        'name' => 'Bug',
        'color' => '#ef4444',
    ]);
});

test('authenticated user can update a label', function () {
    $label = Label::factory()->forUser($this->user)->create(['name' => 'Old Name']);

    actingAs($this->user)
        ->put(route('labels.update', $label->id), [
            'name' => 'New Name',
            'color' => '#22c55e',
        ])
        ->assertRedirect(route('labels.index'));

    assertDatabaseHas('labels', [
        'id' => $label->id,
        'name' => 'New Name',
        'color' => '#22c55e',
    ]);
});

test('authenticated user can delete a label', function () {
    $label = Label::factory()->forUser($this->user)->create();

    actingAs($this->user)
        ->delete(route('labels.destroy', $label->id))
        ->assertRedirect(route('labels.index'));

    assertDatabaseMissing('labels', ['id' => $label->id]);
});

test('user cannot update another user label', function () {
    $otherUser = User::factory()->create();
    $label = Label::factory()->forUser($otherUser)->create();

    actingAs($this->user)
        ->put(route('labels.update', $label->id), [
            'name' => 'Hacked',
            'color' => '#000000',
        ]);

    expect($label->fresh()->name)->not->toBe('Hacked');
});

test('user cannot delete another user label', function () {
    $otherUser = User::factory()->create();
    $label = Label::factory()->forUser($otherUser)->create();

    actingAs($this->user)
        ->delete(route('labels.destroy', $label->id));

    assertDatabaseHas('labels', ['id' => $label->id]);
});

test('label name and color are required', function () {
    actingAs($this->user)
        ->post(route('labels.store'), [])
        ->assertSessionHasErrors(['name', 'color']);
});

test('label color must be valid hex', function () {
    actingAs($this->user)
        ->post(route('labels.store'), [
            'name' => 'Test',
            'color' => 'not-a-color',
        ])
        ->assertSessionHasErrors('color');
});

test('user can assign labels to a task', function () {
    $task = Task::factory()->forUser($this->user)->create();
    $labels = Label::factory()->count(2)->forUser($this->user)->create();

    actingAs($this->user)
        ->put(route('tasks.update', $task->id), [
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status->value,
            'priority' => $task->priority->value,
            'label_ids' => $labels->pluck('id')->toArray(),
        ])
        ->assertRedirect();

    $task->refresh();
    expect($task->labels->count())->toBe(2);
});

test('unauthenticated user cannot access label routes', function () {
    $label = Label::factory()->create();

    $this->get(route('labels.index'))->assertRedirect('/login');
    $this->post(route('labels.store'), ['name' => 'Test', 'color' => '#000'])->assertRedirect('/login');
    $this->put(route('labels.update', $label->id), ['name' => 'Test', 'color' => '#000'])->assertRedirect('/login');
    $this->delete(route('labels.destroy', $label->id))->assertRedirect('/login');
});
