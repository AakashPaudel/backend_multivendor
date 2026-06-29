<?php

namespace App\Http\Resources\Cart;

use App\Http\Resources\ApiResource;
use App\Services\Support\PublicMediaStorageService;
use Illuminate\Http\Request;

class CartItemResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        $product = $this->resource['product'];
        $vendor = $this->resource['vendor'];
        /** @var PublicMediaStorageService $media */
        $media = app(PublicMediaStorageService::class);

        return [
            'id' => $this->resource['id'],
            'quantity' => $this->resource['quantity'],
            'stored_unit_price' => $this->resource['stored_unit_price'],
            'current_unit_price' => $this->resource['current_unit_price'],
            'base_unit_price' => $this->resource['base_unit_price'],
            'subtotal' => $this->resource['subtotal'],
            'discount_total' => $this->resource['discount_total'],
            'line_total' => $this->resource['line_total'],
            'is_available' => $this->resource['is_available'],
            'messages' => $this->resource['messages'],
            'product' => [
                'id' => $product?->id,
                'name' => $product?->name,
                'slug' => $product?->slug,
                'sku' => $product?->sku,
                'status' => $product?->status?->value,
                'stock_quantity' => $product?->stock_quantity,
                'thumbnail_path' => $product?->thumbnail_path,
                'thumbnail_url' => $product?->thumbnail_path ? $media->url($product->thumbnail_path) : null,
            ],
            'vendor' => [
                'id' => $vendor?->id,
                'store_name' => $vendor?->vendorProfile?->store_name,
                'slug' => $vendor?->vendorProfile?->slug,
            ],
        ];
    }
}
