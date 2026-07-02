<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TaskTemplate;
use App\Models\User;

final class TaskTemplatePolicy
{
    /**
     * Determine whether the user can view the template.
     */
    public function view(User $user, TaskTemplate $template): bool
    {
        return $user->id === $template->user_id;
    }

    /**
     * Determine whether the user can update the template.
     */
    public function update(User $user, TaskTemplate $template): bool
    {
        return $user->id === $template->user_id;
    }

    /**
     * Determine whether the user can delete the template.
     */
    public function delete(User $user, TaskTemplate $template): bool
    {
        return $user->id === $template->user_id;
    }
}
