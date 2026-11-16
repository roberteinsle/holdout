<?php

namespace App\Modules\Inventory\Policies;

use App\Models\User;
use App\Modules\Inventory\Models\Batch;

/** Everyone in the household may manage its batches; never another household's. */
class BatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->household_id !== null;
    }

    public function view(User $user, Batch $batch): bool
    {
        return $user->household_id === $batch->product?->household_id;
    }

    public function create(User $user): bool
    {
        return $user->household_id !== null;
    }

    public function update(User $user, Batch $batch): bool
    {
        return $this->view($user, $batch);
    }

    public function delete(User $user, Batch $batch): bool
    {
        return $this->view($user, $batch);
    }

    public function deleteAny(User $user): bool
    {
        return $user->household_id !== null;
    }
}
