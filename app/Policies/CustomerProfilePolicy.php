<?php

namespace App\Policies;

use App\Models\CustomerProfile;
use App\Models\User;

class CustomerProfilePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isCustomer();
    }

    public function view(User $user, CustomerProfile $profile): bool
    {
        return $profile->user_id === $user->id;
    }

    public function update(User $user, CustomerProfile $profile): bool
    {
        return $profile->user_id === $user->id;
    }
}
