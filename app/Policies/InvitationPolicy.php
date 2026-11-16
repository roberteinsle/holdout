<?php

namespace App\Policies;

use App\Models\Invitation;
use App\Models\User;

class InvitationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    public function view(User $user, Invitation $invitation): bool
    {
        return $user->isOwner() && $user->household_id === $invitation->household_id;
    }

    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    public function update(User $user, Invitation $invitation): bool
    {
        return false;
    }

    public function delete(User $user, Invitation $invitation): bool
    {
        return $this->view($user, $invitation);
    }
}
