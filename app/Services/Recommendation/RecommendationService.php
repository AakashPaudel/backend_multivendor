<?php

namespace App\Services\Recommendation;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Recommendation;
use App\Services\Service;
use App\Services\Support\CacheInvalidationService;
use App\Services\Support\PublicMediaStorageService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RecommendationService extends Service
{
    public function __construct(
        private readonly CacheInvalidationService $cacheInvalidationService,
    ) {}

    public function generate(int $limitPerProduct = 8): int
    {
        $paidOrders = Order::query()
            ->where('payment_status', PaymentStatus::Paid)
            ->with('items')
            ->get();

        $totalOrders = max($paidOrders->count(), 1);
        $productOrderCounts = [];
        $pairCounts = [];

        foreach ($paidOrders as $order) {
            $productIds = $order->items
                ->pluck('product_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            foreach ($productIds as $productId) {
                $productOrderCounts[$productId] = ($productOrderCounts[$productId] ?? 0) + 1;
            }

            foreach ($productIds as $baseProductId) {
                foreach ($productIds as $recommendedProductId) {
                    if ($baseProductId === $recommendedProductId) {
                        continue;
                    }

                    $pairCounts[$baseProductId][$recommendedProductId] = ($pairCounts[$baseProductId][$recommendedProductId] ?? 0) + 1;
                }
            }
        }

        $records = [];

        foreach ($pairCounts as $baseProductId => $recommendations) {
            arsort($recommendations);

            foreach (array_slice($recommendations, 0, $limitPerProduct, true) as $recommendedProductId => $pairCount) {
                $baseCount = max($productOrderCounts[$baseProductId] ?? 1, 1);
                $recommendedCount = max($productOrderCounts[$recommendedProductId] ?? 1, 1);
                $support = round($pairCount / $totalOrders, 4);
                $confidence = round($pairCount / $baseCount, 4);
                $lift = round($confidence / ($recommendedCount / $totalOrders), 4);

                $records[] = [
                    'product_id' => $baseProductId,
                    'recommended_product_id' => $recommendedProductId,
                    'support_value' => $support,
                    'confidence_value' => $lift === INF ? 0 : $confidence,
                    'lift_value' => $lift === INF ? 0 : $lift,
                    'generated_at' => now(),
                ];
            }
        }

        DB::transaction(function () use ($records): void {
            Recommendation::query()->delete();

            if ($records !== []) {
                Recommendation::query()->insert($records);
            }
        });

        $this->cacheInvalidationService->bumpRecommendationVersion();

        return count($records);
    }

    public function forProduct(Product $product, int $limit = 8): Collection
    {
        $version = (int) Cache::get('recommendations.version', 1);
        $cacheKey = sprintf('recommendations:v2:%d:%d', $product->id, $version);

        /** @var array<int, array<string, mixed>> $recommendations */
        $recommendations = Cache::remember(
            $cacheKey,
            now()->addMinutes(15),
            fn () => Recommendation::query()
                ->where('product_id', $product->id)
                ->with(['recommendedProduct' => function ($query): void {
                    $query->visible()->with(['category', 'images', 'vendor.vendorProfile']);
                }])
                ->orderByDesc('confidence_value')
                ->limit($limit)
                ->get()
                ->map(fn (Recommendation $recommendation): array => $this->transformRecommendation($recommendation))
                ->values()
                ->all()
        );

        return collect($recommendations)
            ->filter(fn (mixed $recommendation): bool => is_array($recommendation) && array_key_exists('product', $recommendation))
            ->filter(fn (array $recommendation): bool => $recommendation['product'] !== null)
            ->values();
    }

    private function transformRecommendation(Recommendation $recommendation): array
    {
        /** @var PublicMediaStorageService $media */
        $media = app(PublicMediaStorageService::class);
        $product = $recommendation->recommendedProduct;

        return [
            'product_id' => $recommendation->product_id,
            'recommended_product_id' => $recommendation->recommended_product_id,
            'support_value' => $recommendation->support_value,
            'confidence_value' => $recommendation->confidence_value,
            'lift_value' => $recommendation->lift_value,
            'generated_at' => $recommendation->generated_at?->toISOString(),
            'product' => $product ? [
                'id' => $product->id,
                'vendor_id' => $product->vendor_id,
                'category_id' => $product->category_id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'short_description' => $product->short_description,
                'description' => $product->description,
                'price' => $product->price,
                'discount_price' => $product->discount_price,
                'stock_quantity' => $product->stock_quantity,
                'status' => $product->status->value,
                'thumbnail_path' => $product->thumbnail_path,
                'thumbnail_url' => $media->url($product->thumbnail_path),
                'weight' => $product->weight,
                'meta_title' => $product->meta_title,
                'meta_description' => $product->meta_description,
                'is_sellable' => $product->isSellable(),
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'parent_id' => $product->category->parent_id,
                    'name' => $product->category->name,
                    'slug' => $product->category->slug,
                    'description' => $product->category->description,
                    'image_path' => $product->category->image_path,
                    'image_url' => $media->url($product->category->image_path),
                    'is_active' => $product->category->is_active,
                    'sort_order' => $product->category->sort_order,
                    'created_at' => $product->category->created_at?->toISOString(),
                    'updated_at' => $product->category->updated_at?->toISOString(),
                ] : null,
                'images' => $product->images->map(fn ($image): array => [
                    'id' => $image->id,
                    'image_path' => $image->image_path,
                    'image_url' => $media->url($image->image_path),
                    'sort_order' => $image->sort_order,
                ])->values()->all(),
                'vendor' => [
                    'id' => $product->vendor->id,
                    'name' => $product->vendor->name,
                ],
                'created_at' => $product->created_at?->toISOString(),
                'updated_at' => $product->updated_at?->toISOString(),
            ] : null,
        ];
    }
}
