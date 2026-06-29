<?php

namespace App\Policies;

use App\Models\CartItem;
use App\Models\User;

class CartItemPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isCustomer();
    }

    public function create(User $user): bool
    {
        return $user->isCustomer();
    }

    public function update(User $user, CartItem $cartItem): bool
    {
        return $cartItem->cart?->user_id === $user->id;
    }

    public function delete(User $user, CartItem $cartItem): bool
    {
        return $cartItem->cart?->user_id === $user->id;
    }
}
