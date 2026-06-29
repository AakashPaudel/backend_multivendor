<?php

namespace App\Http\Resources\Vendor;

use App\Http\Resources\ApiResource;
use App\Services\Support\PublicMediaStorageService;
use Illuminate\Http\Request;

class PublicVendorResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        /** @var PublicMediaStorageService $media */
        $media = app(PublicMediaStorageService::class);

        return [
            'id' => $this->id,
            'store_name' => $this->store_name,
            'slug' => $this->slug,
            'description' => $this->description,
            'logo_path' => $this->logo_path,
            'logo_url' => $media->url($this->logo_path),
            'banner_path' => $this->banner_path,
            'banner_url' => $media->url($this->banner_path),
            'city' => $this->city,
            'district' => $this->district,
            'country' => $this->country,
            'product_count' => $this->whenCounted('products', $this->products_count),
        ];
    }
}
