<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Label;
use App\Models\User;

final class LabelPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Label $label): bool
    {
        return $user->id === $label->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Label $label): bool
    {
        return $user->id === $label->user_id;
    }

    public function delete(User $user, Label $label): bool
    {
        return $user->id === $label->user_id;
    }
}
