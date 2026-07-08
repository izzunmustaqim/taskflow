<?php

declare(strict_types=1);

namespace Tests\Feature;

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
        Task::factory()->count(3)->create(['user_id' => $this->user->id]);

        $taskService = $this->app->make(\App\Services\TaskService::class);

        $stats = $taskService->getStats($this->user);
        $this->assertEquals(3, $stats['total']);

        // Verify cache exists
        $cached = Cache::get("user:{$this->user->id}:tasks:stats");
        $this->assertNotNull($cached);
    }

    public function test_task_stats_cache_is_invalidated_on_create(): void
    {
        $taskService = $this->app->make(\App\Services\TaskService::class);

        // Populate cache
        $taskService->getStats($this->user);

        // Create task via service (should invalidate cache)
        $taskService->create($this->user, [
            'title' => 'Test Task',
            'status' => 'pending',
            'priority' => 'medium',
        ]);

        // Cache should be cleared
        $cached = Cache::get("user:{$this->user->id}:tasks:stats");
        $this->assertNull($cached);
    }

    public function test_task_stats_cache_is_invalidated_on_delete(): void
    {
        $taskService = $this->app->make(\App\Services\TaskService::class);

        $task = Task::factory()->create(['user_id' => $this->user->id]);

        // Populate cache
        $taskService->getStats($this->user);

        // Delete via service (should invalidate cache)
        $taskService->delete($task);

        // Cache should be cleared
        $cached = Cache::get("user:{$this->user->id}:tasks:stats");
        $this->assertNull($cached);
    }

    public function test_recent_tasks_are_cached(): void
    {
        Task::factory()->count(5)->create(['user_id' => $this->user->id]);

        $taskService = $this->app->make(\App\Services\TaskService::class);

        $recent = $taskService->getRecent($this->user);
        $this->assertCount(5, $recent);

        // Verify cache
        $cached = Cache::get("user:{$this->user->id}:tasks:recent:5");
        $this->assertNotNull($cached);
    }

    public function test_categories_are_cached(): void
    {
        $categoryService = $this->app->make(\App\Services\CategoryService::class);

        // Create via service
        $categoryService->create($this->user, ['name' => 'Work', 'color' => '#3B82F6']);
        $categoryService->create($this->user, ['name' => 'Personal', 'color' => '#10B981']);

        // First call populates cache
        $categories = $categoryService->list($this->user);
        $this->assertCount(2, $categories);

        // Verify cache
        $cached = Cache::get("user:{$this->user->id}:categories:list");
        $this->assertNotNull($cached);
    }

    public function test_categories_cache_is_invalidated_on_create(): void
    {
        $categoryService = $this->app->make(\App\Services\CategoryService::class);

        // Populate cache
        $categoryService->list($this->user);

        // Create via service (should invalidate cache)
        $categoryService->create($this->user, ['name' => 'New', 'color' => '#FF0000']);

        // Cache should be cleared
        $cached = Cache::get("user:{$this->user->id}:categories:list");
        $this->assertNull($cached);
    }

    public function test_labels_are_cached(): void
    {
        $labelService = $this->app->make(\App\Services\LabelService::class);

        // Create via service
        $labelService->create($this->user, ['name' => 'Urgent']);
        $labelService->create($this->user, ['name' => 'Low Priority']);

        // First call populates cache
        $labels = $labelService->list($this->user);
        $this->assertCount(2, $labels);

        // Verify cache
        $cached = Cache::get("user:{$this->user->id}:labels:list");
        $this->assertNotNull($cached);
    }

    public function test_labels_cache_is_invalidated_on_create(): void
    {
        $labelService = $this->app->make(\App\Services\LabelService::class);

        // Populate cache
        $labelService->list($this->user);

        // Create via service (should invalidate cache)
        $labelService->create($this->user, ['name' => 'New Label']);

        // Cache should be cleared
        $cached = Cache::get("user:{$this->user->id}:labels:list");
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
        $cache1 = Cache::get("user:{$this->user->id}:tasks:stats");
        $cache2 = Cache::get("user:{$user2->id}:tasks:stats");

        $this->assertNotNull($cache1);
        $this->assertNotNull($cache2);
        $this->assertEquals(2, $cache1['total']);
        $this->assertEquals(5, $cache2['total']);
    }
}
