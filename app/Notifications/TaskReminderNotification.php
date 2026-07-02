<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TaskReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Task $task
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $dueDate = $this->task->due_at->format('F j, Y \a\t g:i A');
        $timeUntilDue = $this->task->due_at->diffForHumans();

        return (new MailMessage)
            ->subject('Task Reminder: ' . $this->task->title)
            ->greeting("Hello {$notifiable->name}!")
            ->line("This is a reminder for your task \"{$this->task->title}\".")
            ->line("Due Date: {$dueDate}")
            ->line("Time remaining: {$timeUntilDue}")
            ->line("Priority: {$this->task->priority->label()}")
            ->action('View Task', url("/tasks/{$this->task->id}"))
            ->line('Make sure to complete this task before the deadline.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'due_at' => $this->task->due_at->toISOString(),
            'priority' => $this->task->priority->value,
        ];
    }
}
