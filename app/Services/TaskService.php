<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RecurrenceType;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class TaskService
{
    private const CACHE_TTL = 300; // 5 minutes

    private function cacheKey(User $user, string $suffix): string
    {
        return "user:{$user->id}:tasks:{$suffix}";
    }

    private function clearUserCache(User $user): void
    {
        Cache::forget($this->cacheKey($user, 'stats'));
        Cache::forget($this->cacheKey($user, 'recent:5'));
        Cache::forget($this->cacheKey($user, 'recent:10'));
    }

    /**
     * List paginated tasks for a user with optional filters.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<Task>
     */
    public function list(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Task::query()
            ->forUser($user)
            ->with('category')
            ->orderBy('sort_order')
            ->latest('created_at');

        if (isset($filters['status']) && $filters['status'] !== '') {
            $status = TaskStatus::tryFrom($filters['status']);
            if ($status !== null) {
                $query->byStatus($status);
            }
        }

        if (isset($filters['priority']) && $filters['priority'] !== '') {
            $priority = TaskPriority::tryFrom($filters['priority']);
            if ($priority !== null) {
                $query->byPriority($priority);
            }
        }

        if (isset($filters['category_id']) && $filters['category_id'] !== '') {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (isset($filters['search']) && $filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        if (isset($filters['label_id']) && $filters['label_id'] !== '') {
            $query->whereHas('labels', function ($q) use ($filters): void {
                $q->where('labels.id', (int) $filters['label_id']);
            });
        }

        if (isset($filters['due']) && $filters['due'] === 'overdue') {
            $query->overdue();
        } elseif (isset($filters['due']) && $filters['due'] === 'soon') {
            $query->dueSoon();
        }

        // Sorting
        $allowedSorts = ['created_at', 'due_date', 'priority', 'status', 'sort_order'];
        $sortField = isset($filters['sort']) && in_array($filters['sort'], $allowedSorts, true)
            ? $filters['sort']
            : 'created_at';
        $sortDirection = isset($filters['order']) && $filters['order'] === 'asc' ? 'asc' : 'desc';

        $query->reorder($sortField, $sortDirection);

        $perPage = isset($filters['per_page']) ? min((int) $filters['per_page'], 50) : 15;

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * List trashed (soft-deleted) tasks for a user.
     *
     * @return LengthAwarePaginator<Task>
     */
    public function listTrashed(User $user): LengthAwarePaginator
    {
        return Task::query()
            ->onlyTrashed()
            ->forUser($user)
            ->with('category')
            ->latest('deleted_at')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * Create a new task within a database transaction.
     *
     * @param array<string, mixed> $data
     */
    public function create(User $user, array $data): Task
    {
        return DB::transaction(function () use ($user, $data): Task {
            $labelIds = $data['label_ids'] ?? [];
            unset($data['label_ids']);

            // Handle recurrence
            $recurrenceType = $data['recurrence_type'] ?? null;
            $recurrenceInterval = $data['recurrence_interval'] ?? 1;
            unset($data['recurrence_type'], $data['recurrence_interval']);

            if ($recurrenceType && $recurrenceType !== RecurrenceType::None->value) {
                $recurrenceTypeEnum = RecurrenceType::from($recurrenceType);
                $dueDate = isset($data['due_at']) ? \Carbon\Carbon::parse($data['due_at']) : now();
                $data['recurrence_type'] = $recurrenceType;
                $data['recurrence_interval'] = $recurrenceInterval;
                $data['next_occurrence_at'] = $recurrenceTypeEnum->getNextOccurrence($dueDate, $recurrenceInterval);
            }

            /** @var Task $task */
            $task = $user->tasks()->create($data);

            if (!empty($labelIds)) {
                $task->labels()->sync($labelIds);
            }

            $this->logActivity($user, $task, 'created');
            $this->clearUserCache($user);

            return $task->load(['category', 'labels', 'attachments']);
        });
    }

    /**
     * Update an existing task within a database transaction.
     *
     * @param array<string, mixed> $data
     */
    public function update(Task $task, array $data): Task
    {
        return DB::transaction(function () use ($task, $data): Task {
            $labelIds = $data['label_ids'] ?? null;
            unset($data['label_ids']);

            // Handle recurrence
            $recurrenceType = $data['recurrence_type'] ?? null;
            $recurrenceInterval = $data['recurrence_interval'] ?? null;
            unset($data['recurrence_type'], $data['recurrence_interval']);

            if ($recurrenceType !== null) {
                if ($recurrenceType && $recurrenceType !== RecurrenceType::None->value) {
                    $recurrenceTypeEnum = RecurrenceType::from($recurrenceType);
                    $dueDate = isset($data['due_at']) ? \Carbon\Carbon::parse($data['due_at']) : ($task->due_at ?? now());
                    $data['recurrence_type'] = $recurrenceType;
                    $data['recurrence_interval'] = $recurrenceInterval ?? $task->recurrence_interval;
                    $data['next_occurrence_at'] = $recurrenceTypeEnum->getNextOccurrence($dueDate, $data['recurrence_interval']);
                } else {
                    $data['recurrence_type'] = null;
                    $data['recurrence_interval'] = 1;
                    $data['next_occurrence_at'] = null;
                }
            }

            $oldValues = $task->only(array_keys($data));
            $task->update($data);

            if ($labelIds !== null) {
                $task->labels()->sync($labelIds);
            }

            // Check if status changed
            if (isset($data['status']) && $oldValues['status'] !== $data['status']) {
                // Load user relationship for activity logging and recurring task creation
                $task->load('user');

                $this->logActivity(
                    $task->user,
                    $task,
                    'status_changed',
                    [
                        'old_status' => $oldValues['status'],
                        'new_status' => $data['status'],
                    ]
                );

                // Create next occurrence if task is recurring and just completed
                if ($data['status'] === TaskStatus::Completed->value
                    && $task->isRecurring()
                    && $task->next_occurrence_at !== null) {
                    $this->createNextOccurrence($task);
                }
            }

            // Log other changes
            $changes = [];
            foreach ($data as $key => $value) {
                if ($key !== 'status' && ($oldValues[$key] ?? null) !== $value) {
                    $changes[$key] = [
                        'old' => $oldValues[$key] ?? null,
                        'new' => $value,
                    ];
                }
            }

            if (!empty($changes)) {
                $this->logActivity(
                    $task->user,
                    $task,
                    'updated',
                    ['changes' => $changes]
                );
            }

            $this->clearUserCache($task->user);

            return $task->fresh(['category', 'labels', 'attachments']) ?? $task;
        });
    }

    /**
     * Soft-delete a task.
     */
    public function delete(Task $task): bool
    {
        $this->logActivity($task->user, $task, 'deleted');
        $this->clearUserCache($task->user);

        return (bool) $task->delete();
    }

    /**
     * Restore a soft-deleted task.
     */
    public function restore(Task $task): bool
    {
        $result = $task->restore();

        if ($result) {
            $this->logActivity($task->user, $task, 'restored');
            $this->clearUserCache($task->user);
        }

        return $result;
    }

    /**
     * Permanently delete a task.
     */
    public function forceDelete(Task $task): bool
    {
        $this->logActivity($task->user, $task, 'force_deleted');
        $this->clearUserCache($task->user);

        return (bool) $task->forceDelete();
    }

    /**
     * Get dashboard statistics for a user.
     *
     * @return array<string, int>
     */
    public function getStats(User $user): array
    {
        return Cache::remember(
            $this->cacheKey($user, 'stats'),
            self::CACHE_TTL,
            function () use ($user): array {
                $baseCounts = Task::query()
                    ->forUser($user)
                    ->selectRaw("
                        COUNT(*) as total,
                        COUNT(CASE WHEN status = ? THEN 1 END) as pending,
                        COUNT(CASE WHEN status = ? THEN 1 END) as in_progress,
                        COUNT(CASE WHEN status = ? THEN 1 END) as completed
                    ", [
                        TaskStatus::Pending->value,
                        TaskStatus::InProgress->value,
                        TaskStatus::Completed->value,
                    ])
                    ->first();

                $overdue = Task::query()
                    ->forUser($user)
                    ->overdue()
                    ->count();

                $dueSoon = Task::query()
                    ->forUser($user)
                    ->dueSoon()
                    ->count();

                return [
                    'total' => (int) ($baseCounts?->total ?? 0),
                    'pending' => (int) ($baseCounts?->pending ?? 0),
                    'in_progress' => (int) ($baseCounts?->in_progress ?? 0),
                    'completed' => (int) ($baseCounts?->completed ?? 0),
                    'overdue' => $overdue,
                    'due_soon' => $dueSoon,
                ];
            }
        );
    }

    /**
     * Get recent tasks for a user (for the dashboard).
     *
     * @return Collection<int, Task>
     */
    public function getRecent(User $user, int $limit = 5): Collection
    {
        /** @var Collection<int, Task> $tasks */
        $tasks = Cache::remember(
            $this->cacheKey($user, "recent:{$limit}"),
            self::CACHE_TTL,
            function () use ($user, $limit): Collection {
                return Task::query()
                    ->forUser($user)
                    ->with('category')
                    ->latest('created_at')
                    ->limit($limit)
                    ->get();
            }
        );

        return $tasks;
    }

    /**
     * Get activity log for a task.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getActivityLog(Task $task, int $limit = 10)
    {
        return ActivityLog::query()
            ->where('task_id', $task->id)
            ->latest('created_at')
            ->paginate($limit);
    }

    /**
     * Soft-delete multiple tasks in bulk.
     *
     * @param array<int> $ids
     * @return int Number of tasks deleted
     */
    public function bulkDelete(User $user, array $ids): int
    {
        $tasks = Task::query()
            ->forUser($user)
            ->whereIn('id', $ids)
            ->get();

        $count = 0;
        foreach ($tasks as $task) {
            $this->logActivity($user, $task, 'deleted');
            $task->delete();
            $count++;
        }

        return $count;
    }

    /**
     * Update status of multiple tasks in bulk.
     *
     * @param array<int> $ids
     * @return int Number of tasks updated
     */
    public function bulkUpdateStatus(User $user, array $ids, TaskStatus $status): int
    {
        $tasks = Task::query()
            ->forUser($user)
            ->whereIn('id', $ids)
            ->get();

        $count = 0;
        foreach ($tasks as $task) {
            if ($task->status !== $status) {
                $oldStatus = $task->status;
                $task->update(['status' => $status]);
                $this->logActivity($user, $task, 'status_changed', [
                    'old_status' => $oldStatus->value,
                    'new_status' => $status->value,
                ]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Restore multiple soft-deleted tasks in bulk.
     *
     * @param array<int> $ids
     * @return int Number of tasks restored
     */
    public function bulkRestore(User $user, array $ids): int
    {
        $tasks = Task::query()
            ->onlyTrashed()
            ->forUser($user)
            ->whereIn('id', $ids)
            ->get();

        $count = 0;
        foreach ($tasks as $task) {
            $task->restore();
            $this->logActivity($user, $task, 'restored');
            $count++;
        }

        return $count;
    }

    /**
     * Permanently delete multiple trashed tasks in bulk.
     *
     * @param array<int> $ids
     * @return int Number of tasks permanently deleted
     */
    public function bulkForceDelete(User $user, array $ids): int
    {
        $tasks = Task::query()
            ->onlyTrashed()
            ->forUser($user)
            ->whereIn('id', $ids)
            ->get();

        $count = 0;
        foreach ($tasks as $task) {
            $this->logActivity($user, $task, 'force_deleted');
            $task->forceDelete();
            $count++;
        }

        return $count;
    }

    /**
     * Reorder tasks for a user by updating sort_order.
     *
     * @param array<int, int> $orderedIds Task IDs in desired order
     * @return int Number of tasks reordered
     */
    public function reorder(User $user, array $orderedIds): int
    {
        $count = 0;
        foreach ($orderedIds as $position => $id) {
            Task::query()
                ->forUser($user)
                ->where('id', $id)
                ->update(['sort_order' => $position]);
            $count++;
        }

        return $count;
    }

    /**
     * Log an activity for a task.
     */
    private function logActivity(User $user, Task $task, string $type, ?array $properties = null): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $user->id,
            'task_id' => $task->id,
            'type' => $type,
            'properties' => $properties,
        ]);
    }

    /**
     * Create the next occurrence of a recurring task.
     */
    private function createNextOccurrence(Task $task): Task
    {
        $task->load('user');

        $nextDueDate = $task->recurrence_type->getNextOccurrence(
            $task->due_at ?? now(),
            $task->recurrence_interval
        );

        $nextTask = $task->user->tasks()->create([
            'category_id' => $task->category_id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => TaskStatus::Pending,
            'priority' => $task->priority,
            'due_at' => $nextDueDate,
            'recurrence_type' => $task->recurrence_type,
            'recurrence_interval' => $task->recurrence_interval,
            'next_occurrence_at' => $task->recurrence_type->getNextOccurrence(
                $nextDueDate,
                $task->recurrence_interval
            ),
            'parent_task_id' => $task->parent_task_id ?? $task->id,
        ]);

        // Copy labels
        if ($task->labels()->count() > 0) {
            $nextTask->labels()->sync($task->labels->pluck('id'));
        }

        $this->logActivity($task->user, $nextTask, 'created_from_recurring', [
            'parent_task_id' => $task->id,
        ]);

        return $nextTask;
    }

    /**
     * Process all tasks that are due for recurrence.
     *
     * @return int Number of tasks recursed
     */
    public function processRecurringTasks(): int
    {
        $tasks = Task::query()
            ->dueForRecurrence()
            ->get();

        $count = 0;
        foreach ($tasks as $task) {
            $this->createNextOccurrence($task);
            $count++;
        }

        return $count;
    }
}
