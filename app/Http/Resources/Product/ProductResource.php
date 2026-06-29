<?php

namespace App\Http\Resources\Product;

use App\Http\Resources\ApiResource;
use App\Http\Resources\Catalog\CategoryResource;
use App\Services\Support\PublicMediaStorageService;
use Illuminate\Http\Request;

class ProductResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        /** @var PublicMediaStorageService $media */
        $media = app(PublicMediaStorageService::class);

        return [
            'id' => $this->id,
            'vendor_id' => $this->vendor_id,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'price' => $this->price,
            'discount_price' => $this->discount_price,
            'stock_quantity' => $this->stock_quantity,
            'status' => $this->status->value,
            'thumbnail_path' => $this->thumbnail_path,
            'thumbnail_url' => $media->url($this->thumbnail_path),
            'weight' => $this->weight,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'is_sellable' => $this->isSellable(),
            'category' => $this->whenLoaded('category', fn () => new CategoryResource($this->category)),
            'images' => $this->whenLoaded('images', fn () => ProductImageResource::collection($this->images)),
            'vendor' => $this->whenLoaded('vendor', function (): array {
                return [
                    'id' => $this->vendor->id,
                    'name' => $this->vendor->name,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
