<?php

namespace App\Http\Resources\Auth;

use App\Http\Resources\ApiResource;
use App\Http\Resources\Customer\AddressResource;
use App\Http\Resources\Customer\CustomerProfileResource;
use Illuminate\Http\Request;

class UserResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role->value,
            'status' => $this->status->value,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'customer_profile' => $this->whenLoaded(
                'customerProfile',
                fn () => $this->customerProfile ? new CustomerProfileResource($this->customerProfile) : null
            ),
            'vendor_profile' => $this->whenLoaded('vendorProfile', function (): array {
                if (! $this->vendorProfile) {
                    return [];
                }

                return [
                    'id' => $this->vendorProfile->id,
                    'store_name' => $this->vendorProfile->store_name,
                    'slug' => $this->vendorProfile->slug,
                    'approval_status' => $this->vendorProfile->approval_status->value,
                ];
            }),
            'default_address' => $this->when(
                $this->relationLoaded('customerProfile') && $this->customerProfile?->relationLoaded('defaultAddress'),
                fn () => $this->customerProfile?->defaultAddress
                    ? new AddressResource($this->customerProfile->defaultAddress)
                    : null
            ),
        ];
    }
}
