<?php

namespace App\Http\Resources\Product;

use App\Http\Resources\ApiResource;
use App\Services\Support\PublicMediaStorageService;
use Illuminate\Http\Request;

class ProductImageResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        /** @var PublicMediaStorageService $media */
        $media = app(PublicMediaStorageService::class);

        return [
            'id' => $this->id,
            'image_path' => $this->image_path,
            'image_url' => $media->url($this->image_path),
            'sort_order' => $this->sort_order,
        ];
    }
}
