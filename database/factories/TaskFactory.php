<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
final class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->optional(0.7)->paragraph(),
            'status' => fake()->randomElement(TaskStatus::cases()),
            'priority' => fake()->randomElement(TaskPriority::cases()),
            'due_at' => fake()->optional(0.6)->dateTimeBetween('now', '+30 days'),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user->id,
        ]);
    }

    public function withCategory(Category $category): static
    {
        return $this->state(fn (): array => [
            'category_id' => $category->id,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => TaskStatus::Pending,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (): array => [
            'status' => TaskStatus::InProgress,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => TaskStatus::Completed,
        ]);
    }

    public function highPriority(): static
    {
        return $this->state(fn (): array => [
            'priority' => TaskPriority::High,
        ]);
    }

    public function lowPriority(): static
    {
        return $this->state(fn (): array => [
            'priority' => TaskPriority::Low,
        ]);
    }

    public function dueSoon(): static
    {
        return $this->state(fn (): array => [
            'due_at' => now()->addHours(12),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (): array => [
            'due_at' => now()->subDay(),
            'status' => TaskStatus::Pending,
        ]);
    }
}
