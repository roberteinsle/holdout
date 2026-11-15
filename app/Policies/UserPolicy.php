<?php

namespace App\Policies;

use App\Models\User;

/**
 * Everyone in the household may manage the family list. The person using
 * this device cannot remove themselves (switch person first).
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->household_id !== null;
    }

    public function view(User $user, User $model): bool
    {
        return $user->household_id === $model->household_id;
    }

    public function create(User $user): bool
    {
        return $user->household_id !== null;
    }

    public function update(User $user, User $model): bool
    {
        return $user->household_id === $model->household_id;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->household_id === $model->household_id && ! $user->is($model);
    }
}
