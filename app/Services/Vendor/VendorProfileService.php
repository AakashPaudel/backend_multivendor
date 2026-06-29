<?php

namespace App\Services\Vendor;

use App\Models\User;
use App\Models\VendorProfile;
use App\Services\Service;
use App\Services\Support\PublicMediaStorageService;
use App\Services\Support\SlugService;
use Illuminate\Support\Facades\DB;

class VendorProfileService extends Service
{
    public function __construct(
        private readonly SlugService $slugService,
        private readonly PublicMediaStorageService $mediaStorageService,
    ) {}

    public function update(User $user, VendorProfile $profile, array $attributes): VendorProfile
    {
        return DB::transaction(function () use ($profile, $attributes): VendorProfile {
            $storeName = $attributes['store_name'] ?? $profile->store_name;

            $profile->fill([
                'store_name' => $storeName,
                'slug' => $attributes['slug'] ?? $this->slugService->generate($storeName, VendorProfile::class, ignoreId: $profile->id),
                'description' => $attributes['description'] ?? $profile->description,
                'business_email' => $attributes['business_email'] ?? $profile->business_email,
                'business_phone' => $attributes['business_phone'] ?? $profile->business_phone,
                'address_line' => $attributes['address_line'] ?? $profile->address_line,
                'city' => $attributes['city'] ?? $profile->city,
                'district' => $attributes['district'] ?? $profile->district,
                'country' => $attributes['country'] ?? $profile->country,
            ]);

            if (isset($attributes['logo'])) {
                $profile->logo_path = $this->mediaStorageService->replace($profile->logo_path, $attributes['logo'], 'vendors/logos');
            }

            if (isset($attributes['banner'])) {
                $profile->banner_path = $this->mediaStorageService->replace($profile->banner_path, $attributes['banner'], 'vendors/banners');
            }

            $profile->save();

            return $profile->fresh();
        });
    }
}
