<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

final class CategoryService
{
    private const CACHE_TTL = 600; // 10 minutes

    private function clearUserCache(User $user): void
    {
        Cache::forget("user:{$user->id}:categories:list");
    }

    /**
     * List all categories for a user.
     *
     * @return Collection<int, Category>
     */
    public function list(User $user): Collection
    {
        return Cache::remember(
            "user:{$user->id}:categories:list",
            self::CACHE_TTL,
            function () use ($user): Collection {
                return Category::query()
                    ->forUser($user)
                    ->orderBy('name')
                    ->get();
            }
        );
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

        $this->clearUserCache($user);

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

        $this->clearUserCache($category->user);

        return $category;
    }

    /**
     * Delete a category.
     */
    public function delete(Category $category): bool
    {
        $this->clearUserCache($category->user);

        return (bool) $category->delete();
    }
}
