<?php

namespace App\Modules\Inventory\Policies;

use App\Models\User;
use App\Modules\Inventory\Models\Movement;

/** The history can be read by the household, but never edited or deleted by hand. */
class MovementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->household_id !== null;
    }

    public function view(User $user, Movement $movement): bool
    {
        return $user->household_id === $movement->product?->household_id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Movement $movement): bool
    {
        return false;
    }

    public function delete(User $user, Movement $movement): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
