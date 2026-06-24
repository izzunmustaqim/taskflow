<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class TaskService
{
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

        if (isset($filters['due']) && $filters['due'] === 'overdue') {
            $query->overdue();
        } elseif (isset($filters['due']) && $filters['due'] === 'soon') {
            $query->dueSoon();
        }

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
            /** @var Task $task */
            $task = $user->tasks()->create($data);

            return $task->load('category');
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
            $task->update($data);

            return $task->fresh(['category']) ?? $task;
        });
    }

    /**
     * Soft-delete a task.
     */
    public function delete(Task $task): bool
    {
        return (bool) $task->delete();
    }

    /**
     * Restore a soft-deleted task.
     */
    public function restore(Task $task): bool
    {
        return $task->restore();
    }

    /**
     * Permanently delete a task.
     */
    public function forceDelete(Task $task): bool
    {
        return (bool) $task->forceDelete();
    }

    /**
     * Get dashboard statistics for a user.
     *
     * @return array<string, int>
     */
    public function getStats(User $user): array
    {
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

    /**
     * Get recent tasks for a user (for the dashboard).
     *
     * @return Collection<int, Task>
     */
    public function getRecent(User $user, int $limit = 5): Collection
    {
        return Task::query()
            ->forUser($user)
            ->with('category')
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }
}
