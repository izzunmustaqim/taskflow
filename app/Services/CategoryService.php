<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class CategoryService
{
    /**
     * List all categories for a user.
     *
     * @return Collection<int, Category>
     */
    public function list(User $user): Collection
    {
        return Category::query()
            ->forUser($user)
            ->orderBy('name')
            ->get();
    }

    /**
     * Create a new category for a user.
     *
     * @param array<string, mixed> $data
     */
    public function create(User $user, array $data): Category
    {
        /** @var Category $category */
        $category = $user->categories()->create($data);

        return $category;
    }

    /**
     * Update an existing category.
     *
     * @param array<string, mixed> $data
     */
    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $category;
    }

    /**
     * Delete a category.
     */
    public function delete(Category $category): bool
    {
        return (bool) $category->delete();
    }
}
