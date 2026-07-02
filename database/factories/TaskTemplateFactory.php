<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TaskTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskTemplate>
 */
final class TaskTemplateFactory extends Factory
{
    protected $model = TaskTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true) . ' Template',
            'title' => fake()->sentence(4),
            'description' => fake()->optional(0.7)->paragraph(),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'status' => 'pending',
            'category_id' => null,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user->id,
        ]);
    }
}
