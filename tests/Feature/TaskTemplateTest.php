<?php

declare(strict_types=1);

use App\Models\Task;
use App\Models\TaskTemplate;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    $this->user = User::factory()->create();
});

// ─── Template CRUD Tests ──────────────────────────────────────

test('user can view their templates', function () {
    $templates = TaskTemplate::factory()->count(3)->forUser($this->user)->create();

    actingAs($this->user)
        ->get(route('templates.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Templates/Index')->has('templates'));
});

test('user can create a template from a task', function () {
    $task = Task::factory()->forUser($this->user)->create([
        'title' => 'Weekly Report',
        'description' => 'Generate weekly status report',
        'priority' => 'high',
    ]);

    actingAs($this->user)
        ->post(route('templates.store'), [
            'task_id' => $task->id,
            'name' => 'Weekly Report Template',
        ])
        ->assertRedirect();

    assertDatabaseHas('task_templates', [
        'user_id' => $this->user->id,
        'name' => 'Weekly Report Template',
        'title' => 'Weekly Report',
        'description' => 'Generate weekly status report',
        'priority' => 'high',
    ]);
});

test('user can create a template with custom data', function () {
    actingAs($this->user)
        ->post(route('templates.store'), [
            'name' => 'Bug Report Template',
            'title' => 'Bug: [describe issue]',
            'description' => 'Steps to reproduce:',
            'priority' => 'medium',
            'category_id' => null,
        ])
        ->assertRedirect();

    assertDatabaseHas('task_templates', [
        'user_id' => $this->user->id,
        'name' => 'Bug Report Template',
        'title' => 'Bug: [describe issue]',
    ]);
});

test('user can update a template', function () {
    $template = TaskTemplate::factory()->forUser($this->user)->create([
        'name' => 'Old Name',
    ]);

    actingAs($this->user)
        ->put(route('templates.update', $template->id), [
            'name' => 'New Name',
            'title' => $template->title,
        ])
        ->assertRedirect();

    assertDatabaseHas('task_templates', [
        'id' => $template->id,
        'name' => 'New Name',
    ]);
});

test('user can delete a template', function () {
    $template = TaskTemplate::factory()->forUser($this->user)->create();

    actingAs($this->user)
        ->delete(route('templates.destroy', $template->id))
        ->assertRedirect();

    assertDatabaseMissing('task_templates', ['id' => $template->id]);
});

test('user cannot delete another users template', function () {
    $otherUser = User::factory()->create();
    $template = TaskTemplate::factory()->forUser($otherUser)->create();

    actingAs($this->user)
        ->delete(route('templates.destroy', $template->id))
        ->assertForbidden();

    $this->assertDatabaseHas('task_templates', ['id' => $template->id]);
});

// ─── Create Task from Template Tests ─────────────────────────

test('user can create a task from a template', function () {
    $template = TaskTemplate::factory()->forUser($this->user)->create([
        'name' => 'Daily Standup',
        'title' => 'Daily Standup Notes',
        'description' => 'What did you do yesterday?',
        'priority' => 'medium',
        'status' => 'pending',
    ]);

    actingAs($this->user)
        ->post(route('templates.use', $template->id))
        ->assertRedirect();

    assertDatabaseHas('tasks', [
        'user_id' => $this->user->id,
        'title' => 'Daily Standup Notes',
        'description' => 'What did you do yesterday?',
        'priority' => 'medium',
        'status' => 'pending',
    ]);
});

test('user cannot use another users template', function () {
    $otherUser = User::factory()->create();
    $template = TaskTemplate::factory()->forUser($otherUser)->create();

    actingAs($this->user)
        ->post(route('templates.use', $template->id))
        ->assertForbidden();
});

test('template create page shows categories and labels', function () {
    actingAs($this->user)
        ->get(route('templates.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Templates/Create')
            ->has('categories')
            ->has('labels')
            ->has('priorities')
        );
});

test('template edit page shows template data', function () {
    $template = TaskTemplate::factory()->forUser($this->user)->create([
        'name' => 'Test Template',
    ]);

    actingAs($this->user)
        ->get(route('templates.edit', $template->id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Templates/Edit')
            ->has('template')
        );
});
