<?php

namespace App\Http\Requests\Vendor;

use App\Http\Requests\ApiRequest;
use App\Models\VendorProfile;
use Illuminate\Validation\Rule;

class UpdateVendorProfileRequest extends ApiRequest
{
    public function rules(): array
    {
        /** @var VendorProfile $profile */
        $profile = $this->route('vendorProfile') ?? $this->user()->vendorProfile;

        return [
            'store_name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('vendor_profiles', 'slug')->ignore($profile?->id)],
            'description' => ['nullable', 'string'],
            'business_email' => ['nullable', 'email', 'max:255'],
            'business_phone' => ['nullable', 'string', 'max:30'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:5120'],
            'banner' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
