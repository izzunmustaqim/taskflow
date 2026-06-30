<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoadingIndicatorTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_loading_bar_component_file_exists(): void
    {
        $path = resource_path('js/Components/LoadingBar.vue');
        $this->assertFileExists($path);
    }

    public function test_loading_spinner_component_file_exists(): void
    {
        $path = resource_path('js/Components/LoadingSpinner.vue');
        $this->assertFileExists($path);
    }

    public function test_authenticated_layout_imports_loading_bar(): void
    {
        $path = resource_path('js/Layouts/AuthenticatedLayout.vue');
        $content = file_get_contents($path);
        $this->assertStringContainsString("import LoadingBar from '@/Components/LoadingBar.vue'", $content);
        $this->assertStringContainsString('<LoadingBar />', $content);
    }

    public function test_guest_layout_imports_loading_bar(): void
    {
        $path = resource_path('js/Layouts/GuestLayout.vue');
        $content = file_get_contents($path);
        $this->assertStringContainsString("import LoadingBar from '@/Components/LoadingBar.vue'", $content);
        $this->assertStringContainsString('<LoadingBar />', $content);
    }

    public function test_trash_page_imports_loading_spinner(): void
    {
        $path = resource_path('js/Pages/Tasks/Trash.vue');
        $content = file_get_contents($path);
        $this->assertStringContainsString("import LoadingSpinner from '@/Components/LoadingSpinner.vue'", $content);
        $this->assertStringContainsString('restoringId', $content);
    }

    public function test_dashboard_page_renders_successfully(): void
    {
        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_tasks_index_page_renders_successfully(): void
    {
        $this->actingAs($this->user)
            ->get(route('tasks.index'))
            ->assertOk();
    }

    public function test_trash_page_renders_successfully(): void
    {
        $this->actingAs($this->user)
            ->get(route('tasks.trash'))
            ->assertOk();
    }
}
