<?php

namespace App\Http\Resources\Customer;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;

class CustomerProfileResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'default_address_id' => $this->default_address_id,
            'user' => $this->whenLoaded('user', function (): array {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'phone' => $this->user->phone,
                    'role' => $this->user->role->value,
                    'status' => $this->user->status->value,
                    'email_verified_at' => $this->user->email_verified_at?->toISOString(),
                ];
            }),
            'default_address' => $this->whenLoaded(
                'defaultAddress',
                fn () => $this->defaultAddress ? new AddressResource($this->defaultAddress) : null
            ),
        ];
    }
}
