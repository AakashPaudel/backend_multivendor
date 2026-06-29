<?php

namespace App\Services\Customer;

use App\Actions\Customer\SyncCustomerDefaultAddressAction;
use App\Models\Address;
use App\Models\CustomerProfile;
use App\Models\User;
use App\Services\Service;
use Illuminate\Support\Facades\DB;

class CustomerProfileService extends Service
{
    public function __construct(
        private readonly SyncCustomerDefaultAddressAction $syncCustomerDefaultAddressAction,
    ) {}

    public function update(User $user, CustomerProfile $profile, array $attributes): CustomerProfile
    {
        return DB::transaction(function () use ($user, $profile, $attributes): CustomerProfile {
            $user->fill([
                'name' => $attributes['name'] ?? $user->name,
                'phone' => $attributes['phone'] ?? $user->phone,
            ])->save();

            if (array_key_exists('default_address_id', $attributes)) {
                /** @var Address|null $defaultAddress */
                $defaultAddress = $attributes['default_address_id']
                    ? $user->addresses()->findOrFail($attributes['default_address_id'])
                    : null;

                $this->syncCustomerDefaultAddressAction->handle($user, $defaultAddress);
            }

            return $profile->fresh();
        });
    }
}
