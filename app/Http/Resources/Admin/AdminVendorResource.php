<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\ApiResource;
use App\Http\Resources\Vendor\VendorProfileResource;
use Illuminate\Http\Request;

class AdminVendorResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'approval_status' => $this->approval_status->value,
            'approved_at' => $this->approved_at?->toISOString(),
            'rejection_reason' => $this->rejection_reason,
            'vendor' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
            ],
            'profile' => new VendorProfileResource($this->resource),
        ];
    }
}
