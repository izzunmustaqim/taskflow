<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

final class TaskPolicy
{
    /**
     * Any authenticated user can view their own task list.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * A user may only view a task they own.
     */
    public function view(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }

    /**
     * Any authenticated user can create tasks.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * A user may only update a task they own.
     */
    public function update(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }

    /**
     * A user may only soft-delete a task they own.
     */
    public function delete(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }

    /**
     * A user may only restore a task they own.
     */
    public function restore(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }

    /**
     * A user may only permanently delete a task they own.
     */
    public function forceDelete(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }
}
