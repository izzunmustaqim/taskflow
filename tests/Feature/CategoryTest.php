<?php

declare(strict_types=1);

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

test('authenticated user can view categories', function () {
    Category::factory()->count(3)->forUser($this->user)->create();

    actingAs($this->user)
        ->get(route('categories.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Categories/Index')
            ->has('categories', 3)
        );
});

test('user cannot view another users categories', function () {
    $otherUser = User::factory()->create();
    $category = Category::factory()->forUser($otherUser)->create();

    actingAs($this->user)
        ->get(route('categories.edit', $category))
        ->assertForbidden();
});

test('user can create a category', function () {
    actingAs($this->user)
        ->post(route('categories.store'), [
            'name' => 'Work',
            'color' => '#ef4444',
        ])
        ->assertRedirect(route('categories.index'))
        ->assertSessionHas('success', 'Category created successfully.');

    assertDatabaseHas('categories', [
        'user_id' => $this->user->id,
        'name' => 'Work',
        'color' => '#ef4444',
    ]);
});

test('category color must be valid hex', function () {
    actingAs($this->user)
        ->post(route('categories.store'), [
            'name' => 'Work',
            'color' => 'red', // invalid
        ])
        ->assertSessionHasErrors(['color']);

    assertDatabaseCount('categories', 0);
});

test('user can update their category', function () {
    $category = Category::factory()->forUser($this->user)->create([
        'name' => 'Old Name',
    ]);

    actingAs($this->user)
        ->put(route('categories.update', $category), [
            'name' => 'New Name',
            'color' => '#000000',
        ])
        ->assertRedirect(route('categories.index'));

    expect($category->fresh()->name)->toBe('New Name');
});

test('user cannot update another users category', function () {
    $otherUser = User::factory()->create();
    $category = Category::factory()->forUser($otherUser)->create();

    actingAs($this->user)
        ->put(route('categories.update', $category), [
            'name' => 'Hacked',
            'color' => '#000000',
        ])
        ->assertForbidden();
});

test('user can delete their category', function () {
    $category = Category::factory()->forUser($this->user)->create();

    actingAs($this->user)
        ->delete(route('categories.destroy', $category))
        ->assertRedirect(route('categories.index'));

    assertDatabaseMissing('categories', ['id' => $category->id]);
});

test('user cannot delete another users category', function () {
    $otherUser = User::factory()->create();
    $category = Category::factory()->forUser($otherUser)->create();

    actingAs($this->user)
        ->delete(route('categories.destroy', $category))
        ->assertForbidden();

    assertDatabaseHas('categories', ['id' => $category->id]);
});

test('deleting a category nullifies category_id on tasks', function () {
    $category = Category::factory()->forUser($this->user)->create();
    $task = Task::factory()->forUser($this->user)->withCategory($category)->create();

    actingAs($this->user)
        ->delete(route('categories.destroy', $category));

    expect($task->fresh()->category_id)->toBeNull();
});
