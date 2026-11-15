<?php

namespace App\Modules\Inventory\Policies;

use App\Models\User;
use App\Modules\Inventory\Models\Product;

/** Everyone in the household may manage its products; never another household's. */
class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->household_id !== null;
    }

    public function view(User $user, Product $product): bool
    {
        return $user->household_id === $product->household_id;
    }

    public function create(User $user): bool
    {
        return $user->household_id !== null;
    }

    public function update(User $user, Product $product): bool
    {
        return $this->view($user, $product);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->view($user, $product);
    }

    public function deleteAny(User $user): bool
    {
        return $user->household_id !== null;
    }
}
