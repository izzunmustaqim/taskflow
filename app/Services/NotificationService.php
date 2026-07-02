<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskDueSoonNotification;
use App\Notifications\TaskOverdueNotification;
use App\Notifications\TaskReminderNotification;

final class NotificationService
{
    /**
     * Create an in-app notification.
     */
    public function createInAppNotification(
        User $user,
        string $type,
        string $title,
        string $message,
        ?array $data = null
    ): Notification {
        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'is_read' => false,
        ]);
    }

    /**
     * Send due soon notification for a task.
     */
    public function sendDueSoonNotification(Task $task): void
    {
        $user = $task->user;
        $preferences = NotificationPreference::getOrCreateForUser($user);

        // In-app notification
        if ($preferences->in_app_due_soon) {
            $this->createInAppNotification(
                $user,
                'due_soon',
                'Task Due Soon',
                "Your task \"{$task->title}\" is due soon.",
                ['task_id' => $task->id]
            );
        }

        // Email notification
        if ($preferences->email_due_soon) {
            $user->notify(new TaskDueSoonNotification($task));
        }
    }

    /**
     * Send overdue notification for a task.
     */
    public function sendOverdueNotification(Task $task): void
    {
        $user = $task->user;
        $preferences = NotificationPreference::getOrCreateForUser($user);

        // In-app notification
        if ($preferences->in_app_overdue) {
            $this->createInAppNotification(
                $user,
                'overdue',
                'Task Overdue',
                "Your task \"{$task->title}\" is now overdue.",
                ['task_id' => $task->id]
            );
        }

        // Email notification
        if ($preferences->email_overdue) {
            $user->notify(new TaskOverdueNotification($task));
        }
    }

    /**
     * Send reminder notification for a task.
     */
    public function sendReminderNotification(Task $task): void
    {
        $user = $task->user;
        $preferences = NotificationPreference::getOrCreateForUser($user);

        // In-app notification
        if ($preferences->in_app_reminders) {
            $this->createInAppNotification(
                $user,
                'reminder',
                'Task Reminder',
                "Reminder: Your task \"{$task->title}\" is due {$task->due_at->diffForHumans()}.",
                ['task_id' => $task->id]
            );
        }

        // Email notification
        if ($preferences->email_reminders) {
            $user->notify(new TaskReminderNotification($task));
        }
    }

    /**
     * Process all due soon notifications.
     */
    public function processDueSoonNotifications(): int
    {
        $tasks = Task::whereNotNull('due_at')
            ->where('due_at', '<=', now()->addHours(2))
            ->where('due_at', '>', now())
            ->where('status', '!=', 'completed')
            ->whereDoesntHave('notifications', function ($query) {
                $query->where('type', 'due_soon')
                    ->where('created_at', '>=', now()->subHours(12));
            })
            ->with('user')
            ->get();

        $count = 0;
        foreach ($tasks as $task) {
            $this->sendDueSoonNotification($task);
            $count++;
        }

        return $count;
    }

    /**
     * Process all overdue notifications.
     */
    public function processOverdueNotifications(): int
    {
        $tasks = Task::whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->where('status', '!=', 'completed')
            ->whereDoesntHave('notifications', function ($query) {
                $query->where('type', 'overdue')
                    ->where('created_at', '>=', now()->subHours(24));
            })
            ->with('user')
            ->get();

        $count = 0;
        foreach ($tasks as $task) {
            $this->sendOverdueNotification($task);
            $count++;
        }

        return $count;
    }

    /**
     * Process all reminder notifications.
     */
    public function processReminderNotifications(): int
    {
        $users = User::whereHas('notificationPreference', function ($query) {
            $query->where('in_app_reminders', true)
                ->orWhere('email_reminders', true);
        })->get();

        $count = 0;
        foreach ($users as $user) {
            $preferences = NotificationPreference::getOrCreateForUser($user);
            $reminderTime = now()->addHours($preferences->reminder_hours_before);

            $tasks = Task::forUser($user)
                ->whereNotNull('due_at')
                ->where('due_at', '<=', $reminderTime)
                ->where('due_at', '>', now())
                ->where('status', '!=', 'completed')
                ->whereDoesntHave('notifications', function ($query) use ($user) {
                    $query->where('type', 'reminder')
                        ->where('user_id', $user->id)
                        ->where('created_at', '>=', now()->subHours(12));
                })
                ->get();

            foreach ($tasks as $task) {
                $this->sendReminderNotification($task);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get notifications for a user.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getNotificationsForUser(User $user, int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Notification::forUser($user)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get unread notification count for a user.
     */
    public function getUnreadCount(User $user): int
    {
        return Notification::getUnreadCount($user);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Notification $notification): bool
    {
        return $notification->markAsRead();
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(User $user): int
    {
        return Notification::markAllAsRead($user);
    }

    /**
     * Delete old notifications (older than 30 days).
     */
    public function deleteOldNotifications(int $days = 30): int
    {
        return Notification::where('created_at', '<', now()->subDays($days))->delete();
    }
}
