<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
final class AttachmentFactory extends Factory
{
    protected $model = Attachment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $originalName = $this->faker->word() . '.' . $this->faker->fileExtension();
        $storedName = uniqid('attachment_', true) . '.' . pathinfo($originalName, PATHINFO_EXTENSION);
        $mimeTypes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'application/msword',
            'text/plain',
        ];

        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'mime_type' => $this->faker->randomElement($mimeTypes),
            'size' => $this->faker->numberBetween(1024, 10485760), // 1KB - 10MB
            'path' => 'attachments/' . $storedName,
        ];
    }

    public function forTask(Task $task): static
    {
        return $this->state(fn (): array => [
            'task_id' => $task->id,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user->id,
        ]);
    }
}
