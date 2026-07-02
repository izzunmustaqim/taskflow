<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TaskOverdueNotification extends Notification implements ShouldQueue
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
        $overdueSince = $this->task->due_at->diffForHumans();

        return (new MailMessage)
            ->subject('Task Overdue: ' . $this->task->title)
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your task \"{$this->task->title}\" is now overdue.")
            ->line("Original Due Date: {$dueDate}")
            ->line("Overdue since: {$overdueSince}")
            ->line("Priority: {$this->task->priority->label()}")
            ->action('View Task', url("/tasks/{$this->task->id}"))
            ->line('Please update the task status or reschedule the due date.');
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
