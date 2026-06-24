<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

final class CategoryPolicy
{
    /**
     * Any authenticated user can view their own category list.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * A user may only view a category they own.
     */
    public function view(User $user, Category $category): bool
    {
        return $user->id === $category->user_id;
    }

    /**
     * Any authenticated user can create categories.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * A user may only update a category they own.
     */
    public function update(User $user, Category $category): bool
    {
        return $user->id === $category->user_id;
    }

    /**
     * A user may only delete a category they own.
     */
    public function delete(User $user, Category $category): bool
    {
        return $user->id === $category->user_id;
    }
}
