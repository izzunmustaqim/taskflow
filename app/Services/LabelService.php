<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Label;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class LabelService
{
    /**
     * List all labels for a user.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Label>
     */
    public function list(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return Label::query()
            ->forUser($user)
            ->withCount('tasks')
            ->orderBy('name')
            ->get();
    }

    /**
     * Create a new label.
     */
    public function create(User $user, array $data): Label
    {
        return DB::transaction(function () use ($user, $data): Label {
            /** @var Label $label */
            $label = $user->labels()->create($data);
            return $label;
        });
    }

    /**
     * Update an existing label.
     */
    public function update(Label $label, array $data): Label
    {
        return DB::transaction(function () use ($label, $data): Label {
            $label->update($data);
            return $label->fresh();
        });
    }

    /**
     * Delete a label.
     */
    public function delete(Label $label): bool
    {
        return (bool) $label->delete();
    }

    /**
     * Assign labels to a task.
     *
     * @param array<int> $labelIds
     */
    public function syncTaskLabels(\App\Models\Task $task, array $labelIds): void
    {
        $task->labels()->sync($labelIds);
    }
}
