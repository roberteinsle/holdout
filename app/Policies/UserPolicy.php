<?php

namespace App\Policies;

use App\Models\User;

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
        return false; // people join by invitation
    }

    public function update(User $user, User $model): bool
    {
        return false;
    }

    /** Owners can remove other members of their household, never themselves. */
    public function delete(User $user, User $model): bool
    {
        return $user->isOwner()
            && $user->household_id === $model->household_id
            && ! $user->is($model);
    }
}
