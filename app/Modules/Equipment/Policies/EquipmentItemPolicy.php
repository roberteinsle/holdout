<?php

namespace App\Modules\Equipment\Policies;

use App\Models\User;
use App\Modules\Equipment\Models\EquipmentItem;

/** Everyone in the household may manage its equipment; never another household's. */
class EquipmentItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->household_id !== null;
    }

    public function view(User $user, EquipmentItem $item): bool
    {
        return $user->household_id === $item->household_id;
    }

    public function create(User $user): bool
    {
        return $user->household_id !== null;
    }

    public function update(User $user, EquipmentItem $item): bool
    {
        return $this->view($user, $item);
    }

    public function delete(User $user, EquipmentItem $item): bool
    {
        return $this->view($user, $item);
    }

    public function deleteAny(User $user): bool
    {
        return $user->household_id !== null;
    }
}
