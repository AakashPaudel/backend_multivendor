<?php

namespace App\Services\Customer;

use App\Actions\Customer\SyncCustomerDefaultAddressAction;
use App\Models\Address;
use App\Models\User;
use App\Services\Service;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CustomerAddressService extends Service
{
    public function __construct(
        private readonly SyncCustomerDefaultAddressAction $syncCustomerDefaultAddressAction,
    ) {}

    public function listForUser(User $user): Collection
    {
        return $user->addresses()->latest('id')->get();
    }

    public function create(User $user, array $attributes): Address
    {
        return DB::transaction(function () use ($user, $attributes): Address {
            $shouldBeDefault = ($attributes['is_default'] ?? false) || ! $user->addresses()->exists();

            $address = $user->addresses()->create([
                ...$attributes,
                'country' => $attributes['country'] ?? 'Nepal',
                'is_default' => false,
            ]);

            if ($shouldBeDefault) {
                $this->syncCustomerDefaultAddressAction->handle($user, $address);
            }

            return $user->addresses()->findOrFail($address->id);
        });
    }

    public function update(User $user, Address $address, array $attributes): Address
    {
        return DB::transaction(function () use ($user, $address, $attributes): Address {
            $wasDefault = $address->is_default;

            $address->fill($attributes);
            $address->save();

            if (array_key_exists('is_default', $attributes)) {
                if ($attributes['is_default']) {
                    $this->syncCustomerDefaultAddressAction->handle($user, $address);
                } elseif ($wasDefault) {
                    $this->syncCustomerDefaultAddressAction->handle($user, null);
                }
            }

            return $user->addresses()->findOrFail($address->id);
        });
    }

    public function delete(User $user, Address $address): void
    {
        DB::transaction(function () use ($user, $address): void {
            $wasDefault = $address->is_default;
            $address->delete();

            if ($wasDefault) {
                $this->syncCustomerDefaultAddressAction->handle($user, null);
            }
        });
    }
}
