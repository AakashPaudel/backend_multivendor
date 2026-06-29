<?php

namespace App\Actions\Customer;

use App\Actions\Action;
use App\Models\Address;
use App\Models\CustomerProfile;
use App\Models\User;

class SyncCustomerDefaultAddressAction extends Action
{
    public function handle(User $user, ?Address $defaultAddress): void
    {
        $user->addresses()->update([
            'is_default' => false,
        ]);

        if ($defaultAddress) {
            $user->addresses()
                ->whereKey($defaultAddress->id)
                ->update([
                    'is_default' => true,
                ]);
        }

        CustomerProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['default_address_id' => $defaultAddress?->id]
        );
    }
}
