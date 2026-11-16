<?php

namespace App\Modules\Inventory\Policies;

use App\Models\User;
use App\Modules\Inventory\Models\Location;

/** Everyone in the household may manage its locations; never another household's. */
class LocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->household_id !== null;
    }

    public function view(User $user, Location $location): bool
    {
        return $user->household_id === $location->household_id;
    }

    public function create(User $user): bool
    {
        return $user->household_id !== null;
    }

    public function update(User $user, Location $location): bool
    {
        return $this->view($user, $location);
    }

    public function delete(User $user, Location $location): bool
    {
        return $this->view($user, $location);
    }

    public function deleteAny(User $user): bool
    {
        return $user->household_id !== null;
    }
}
