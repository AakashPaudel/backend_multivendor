<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorOrder;

class VendorOrderPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isVendor();
    }

    public function view(User $user, VendorOrder $vendorOrder): bool
    {
        return $vendorOrder->vendor_id === $user->id;
    }

    public function update(User $user, VendorOrder $vendorOrder): bool
    {
        return $vendorOrder->vendor_id === $user->id;
    }
}
