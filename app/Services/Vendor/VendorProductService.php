<?php

namespace App\Services\Vendor;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use App\Services\Service;
use App\Services\Support\PublicMediaStorageService;
use App\Services\Support\SkuService;
use App\Services\Support\SlugService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VendorProductService extends Service
{
    public function __construct(
        private readonly SlugService $slugService,
        private readonly SkuService $skuService,
        private readonly PublicMediaStorageService $mediaStorageService,
    ) {}

    public function paginateForVendor(User $vendor): LengthAwarePaginator
    {
        return $vendor->products()
            ->with(['category', 'images', 'vendor.vendorProfile'])
            ->latest('id')
            ->paginate();
    }

    public function create(User $vendor, array $attributes): Product
    {
        return DB::transaction(function () use ($attributes, $vendor): Product {
            $status = $this->normalizeStatus($vendor, $attributes['status'] ?? ProductStatus::Draft);

            $product = Product::query()->create([
                'vendor_id' => $vendor->id,
                'category_id' => $attributes['category_id'],
                'name' => $attributes['name'],
                'slug' => $attributes['slug'] ?? $this->slugService->generate($attributes['name'], Product::class),
                'sku' => $attributes['sku'] ?? $this->skuService->generate($attributes['name']),
                'short_description' => $attributes['short_description'] ?? null,
                'description' => $attributes['description'] ?? null,
                'price' => $attributes['price'],
                'discount_price' => $attributes['discount_price'] ?? null,
                'stock_quantity' => $attributes['stock_quantity'],
                'status' => $status,
                'weight' => $attributes['weight'] ?? null,
                'meta_title' => $attributes['meta_title'] ?? null,
                'meta_description' => $attributes['meta_description'] ?? null,
            ]);

            $this->syncMedia($product, $attributes);

            return $product->fresh(['category', 'images', 'vendor.vendorProfile']);
        });
    }

    public function update(User $vendor, Product $product, array $attributes): Product
    {
        return DB::transaction(function () use ($attributes, $product, $vendor): Product {
            $name = $attributes['name'] ?? $product->name;
            $status = array_key_exists('status', $attributes)
                ? $this->normalizeStatus($vendor, $attributes['status'])
                : $product->status;

            $product->fill([
                'category_id' => $attributes['category_id'] ?? $product->category_id,
                'name' => $name,
                'slug' => $attributes['slug'] ?? $this->slugService->generate($name, Product::class, ignoreId: $product->id),
                'sku' => $attributes['sku'] ?? $this->skuService->generate($name, ignoreId: $product->id),
                'short_description' => $attributes['short_description'] ?? $product->short_description,
                'description' => $attributes['description'] ?? $product->description,
                'price' => $attributes['price'] ?? $product->price,
                'discount_price' => array_key_exists('discount_price', $attributes) ? $attributes['discount_price'] : $product->discount_price,
                'stock_quantity' => $attributes['stock_quantity'] ?? $product->stock_quantity,
                'status' => $status,
                'weight' => array_key_exists('weight', $attributes) ? $attributes['weight'] : $product->weight,
                'meta_title' => array_key_exists('meta_title', $attributes) ? $attributes['meta_title'] : $product->meta_title,
                'meta_description' => array_key_exists('meta_description', $attributes) ? $attributes['meta_description'] : $product->meta_description,
            ]);
            $product->save();

            $this->syncMedia($product, $attributes);

            return $product->fresh(['category', 'images', 'vendor.vendorProfile']);
        });
    }

    public function delete(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $product->loadMissing('images');

            $this->mediaStorageService->delete($product->thumbnail_path);

            foreach ($product->images as $image) {
                $this->mediaStorageService->delete($image->image_path);
            }

            $product->images()->delete();
            $product->delete();
        });
    }

    protected function syncMedia(Product $product, array $attributes): void
    {
        if (isset($attributes['thumbnail'])) {
            $product->thumbnail_path = $this->mediaStorageService->replace($product->thumbnail_path, $attributes['thumbnail'], 'products/thumbnails');
            $product->save();
        }

        if (isset($attributes['images'])) {
            foreach ($product->images as $image) {
                $this->mediaStorageService->delete($image->image_path);
            }

            $product->images()->delete();

            foreach ($attributes['images'] as $index => $image) {
                $product->images()->create([
                    'image_path' => $this->mediaStorageService->store($image, 'products/gallery'),
                    'sort_order' => $index,
                ]);
            }
        }
    }

    protected function normalizeStatus(User $vendor, ProductStatus|string $status): ProductStatus
    {
        $status = $status instanceof ProductStatus ? $status : ProductStatus::from($status);

        if ($status === ProductStatus::Active && ! $vendor->vendorProfile?->isApproved()) {
            throw ValidationException::withMessages([
                'status' => ['Only approved vendors can activate products for sale.'],
            ]);
        }

        return $status;
    }
}
