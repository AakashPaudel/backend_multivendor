<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isVendor();
    }

    public function view(User $user, Product $product): bool
    {
        return $product->vendor_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isVendor();
    }

    public function update(User $user, Product $product): bool
    {
        return $product->vendor_id === $user->id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $product->vendor_id === $user->id;
    }
}
