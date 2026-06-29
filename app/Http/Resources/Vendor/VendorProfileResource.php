<?php

namespace App\Http\Resources\Vendor;

use App\Http\Resources\ApiResource;
use App\Services\Support\PublicMediaStorageService;
use Illuminate\Http\Request;

class VendorProfileResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        /** @var PublicMediaStorageService $media */
        $media = app(PublicMediaStorageService::class);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'store_name' => $this->store_name,
            'slug' => $this->slug,
            'description' => $this->description,
            'logo_path' => $this->logo_path,
            'logo_url' => $media->url($this->logo_path),
            'banner_path' => $this->banner_path,
            'banner_url' => $media->url($this->banner_path),
            'business_email' => $this->business_email,
            'business_phone' => $this->business_phone,
            'address_line' => $this->address_line,
            'city' => $this->city,
            'district' => $this->district,
            'country' => $this->country,
            'approval_status' => $this->approval_status->value,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toISOString(),
            'rejection_reason' => $this->rejection_reason,
            'commission_rate_override' => $this->commission_rate_override,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
