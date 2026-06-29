<?php

namespace App\Actions\Auth;

use App\Actions\Action;
use App\Enums\VendorApprovalStatus;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Support\Str;

class CreateVendorProfileAction extends Action
{
    public function handle(User $user, array $attributes): VendorProfile
    {
        return VendorProfile::query()->create([
            'user_id' => $user->id,
            'store_name' => $attributes['store_name'],
            'slug' => $attributes['slug'] ?? Str::slug($attributes['store_name']),
            'description' => $attributes['description'] ?? null,
            'business_email' => $attributes['business_email'] ?? null,
            'business_phone' => $attributes['business_phone'] ?? null,
            'address_line' => $attributes['address_line'] ?? null,
            'city' => $attributes['city'] ?? null,
            'district' => $attributes['district'] ?? null,
            'country' => $attributes['country'] ?? 'Nepal',
            'approval_status' => VendorApprovalStatus::Pending,
        ]);
    }
}
