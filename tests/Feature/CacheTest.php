<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Label;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class CacheTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_task_stats_are_cached(): void
    {
        // Create some tasks
        Task::factory()->count(3)->create(['user_id' => $this->user->id]);

        // First call - should query database
        $stats1 = $this->app->make(\App\Services\TaskService::class)->getStats($this->user);

        $this->assertArrayHasKey('total', $stats1);
        $this->assertEquals(3, $stats1['total']);

        // Verify cache tag exists
        $cacheKey = "user:{$this->user->id}:tasks:stats";
        $cached = Cache::tags(["user:{$this->user->id}:tasks"])->get($cacheKey);
        $this->assertNotNull($cached);
        $this->assertEquals($stats1, $cached);
    }

    public function test_task_stats_cache_is_invalidated_on_create(): void
    {
        $taskService = $this->app->make(\App\Services\TaskService::class);

        // Initial call populates cache
        $taskService->getStats($this->user);

        // Create a task
        Task::factory()->create(['user_id' => $this->user->id]);

        // Cache should be invalidated
        $cacheKey = "user:{$this->user->id}:tasks:stats";
        $cached = Cache::tags(["user:{$this->user->id}:tasks"])->get($cacheKey);
        $this->assertNull($cached);
    }

    public function test_task_stats_cache_is_invalidated_on_delete(): void
    {
        $taskService = $this->app->make(\App\Services\TaskService::class);

        $task = Task::factory()->create(['user_id' => $this->user->id]);

        // Initial call populates cache
        $taskService->getStats($this->user);

        // Delete the task
        $taskService->delete($task);

        // Cache should be invalidated
        $cacheKey = "user:{$this->user->id}:tasks:stats";
        $cached = Cache::tags(["user:{$this->user->id}:tasks"])->get($cacheKey);
        $this->assertNull($cached);
    }

    public function test_recent_tasks_are_cached(): void
    {
        Task::factory()->count(5)->create(['user_id' => $this->user->id]);

        $taskService = $this->app->make(\App\Services\TaskService::class);

        // First call
        $recent1 = $taskService->getRecent($this->user);
        $this->assertCount(5, $recent1);

        // Verify cache
        $cacheKey = "user:{$this->user->id}:tasks:recent:5";
        $cached = Cache::tags(["user:{$this->user->id}:tasks"])->get($cacheKey);
        $this->assertNotNull($cached);
    }

    public function test_categories_are_cached(): void
    {
        Category::factory()->count(3)->create(['user_id' => $this->user->id]);

        $categoryService = $this->app->make(\App\Services\CategoryService::class);

        // First call
        $categories1 = $categoryService->list($this->user);
        $this->assertCount(3, $categories1);

        // Verify cache
        $cacheKey = "user:{$this->user->id}:categories:list";
        $cached = Cache::tags(["user:{$this->user->id}:categories"])->get($cacheKey);
        $this->assertNotNull($cached);
    }

    public function test_categories_cache_is_invalidated_on_create(): void
    {
        $categoryService = $this->app->make(\App\Services\CategoryService::class);

        // Initial call populates cache
        $categoryService->list($this->user);

        // Create a category
        Category::factory()->create(['user_id' => $this->user->id]);

        // Cache should be invalidated
        $cacheKey = "user:{$this->user->id}:categories:list";
        $cached = Cache::tags(["user:{$this->user->id}:categories"])->get($cacheKey);
        $this->assertNull($cached);
    }

    public function test_labels_are_cached(): void
    {
        Label::factory()->count(4)->create(['user_id' => $this->user->id]);

        $labelService = $this->app->make(\App\Services\LabelService::class);

        // First call
        $labels1 = $labelService->list($this->user);
        $this->assertCount(4, $labels1);

        // Verify cache
        $cacheKey = "user:{$this->user->id}:labels:list";
        $cached = Cache::tags(["user:{$this->user->id}:labels"])->get($cacheKey);
        $this->assertNotNull($cached);
    }

    public function test_labels_cache_is_invalidated_on_create(): void
    {
        $labelService = $this->app->make(\App\Services\LabelService::class);

        // Initial call populates cache
        $labelService->list($this->user);

        // Create a label
        Label::factory()->create(['user_id' => $this->user->id]);

        // Cache should be invalidated
        $cacheKey = "user:{$this->user->id}:labels:list";
        $cached = Cache::tags(["user:{$this->user->id}:labels"])->get($cacheKey);
        $this->assertNull($cached);
    }

    public function test_each_user_has_separate_cache(): void
    {
        $user2 = User::factory()->create();

        Task::factory()->count(2)->create(['user_id' => $this->user->id]);
        Task::factory()->count(5)->create(['user_id' => $user2->id]);

        $taskService = $this->app->make(\App\Services\TaskService::class);

        $stats1 = $taskService->getStats($this->user);
        $stats2 = $taskService->getStats($user2);

        $this->assertEquals(2, $stats1['total']);
        $this->assertEquals(5, $stats2['total']);

        // Verify separate cache keys
        $cache1 = Cache::tags(["user:{$this->user->id}:tasks"])->get("user:{$this->user->id}:tasks:stats");
        $cache2 = Cache::tags(["user:{$user2->id}:tasks"])->get("user:{$user2->id}:tasks:stats");

        $this->assertNotNull($cache1);
        $this->assertNotNull($cache2);
        $this->assertEquals(2, $cache1['total']);
        $this->assertEquals(5, $cache2['total']);
    }
}
