<?php

namespace App\Observers;

use App\Models\Category;
use App\Models\Commission;
use App\Models\Order;
use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Models\VendorOrder;
use App\Models\VendorProfile;
use App\Services\Support\CacheInvalidationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;

class CacheInvalidationObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(
        private readonly CacheInvalidationService $cacheInvalidationService,
    ) {}

    public function saved(Model $model): void
    {
        $this->invalidate($model);
    }

    public function deleted(Model $model): void
    {
        $this->invalidate($model);
    }

    private function invalidate(Model $model): void
    {
        match (true) {
            $model instanceof User => $this->cacheInvalidationService->forgetAdminDashboard(),
            $model instanceof VendorProfile => $this->invalidateVendorProfile($model),
            $model instanceof Product => $this->invalidateProduct($model),
            $model instanceof ProductImage => $this->cacheInvalidationService->bumpRecommendationVersion(),
            $model instanceof Category => $this->invalidateCategory(),
            $model instanceof Order => $this->invalidateOrder($model),
            $model instanceof VendorOrder => $this->invalidateVendorOrder($model),
            $model instanceof PlatformSetting => $this->invalidatePlatformSettings(),
            $model instanceof Commission => $this->cacheInvalidationService->forgetAdminDashboard(),
            default => null,
        };
    }

    private function invalidateVendorProfile(VendorProfile $vendorProfile): void
    {
        $this->cacheInvalidationService->forgetAdminDashboard();
        $this->cacheInvalidationService->forgetVendorDashboard($vendorProfile->user_id);
        $this->cacheInvalidationService->bumpRecommendationVersion();
    }

    private function invalidateProduct(Product $product): void
    {
        $this->cacheInvalidationService->forgetVendorDashboard($product->vendor_id);
        $this->cacheInvalidationService->bumpRecommendationVersion();
    }

    private function invalidateCategory(): void
    {
        $this->cacheInvalidationService->forgetCategoryIndex();
        $this->cacheInvalidationService->bumpRecommendationVersion();
    }

    private function invalidateOrder(Order $order): void
    {
        $this->cacheInvalidationService->forgetAdminDashboard();
        $this->cacheInvalidationService->forgetVendorDashboardsForOrder($order);
    }

    private function invalidateVendorOrder(VendorOrder $vendorOrder): void
    {
        $this->cacheInvalidationService->forgetAdminDashboard();
        $this->cacheInvalidationService->forgetVendorDashboard($vendorOrder->vendor_id);
    }

    private function invalidatePlatformSettings(): void
    {
        $this->cacheInvalidationService->forgetAdminSettings();
        $this->cacheInvalidationService->forgetAdminDashboard();
    }
}
