<?php

namespace App\Services\Support;

use App\Models\Order;
use Illuminate\Support\Facades\Cache;

class CacheInvalidationService
{
    public function forgetAdminDashboard(): void
    {
        Cache::forget('admin:dashboard');
    }

    public function forgetAdminSettings(): void
    {
        Cache::forget('admin:settings');
    }

    public function forgetVendorDashboard(?int $vendorId): void
    {
        if (! $vendorId) {
            return;
        }

        Cache::forget('vendor:dashboard:'.$vendorId);
    }

    public function forgetVendorDashboardsForOrder(Order $order): void
    {
        $vendorIds = $order->relationLoaded('vendorOrders')
            ? $order->vendorOrders->pluck('vendor_id')
            : $order->vendorOrders()->pluck('vendor_id');

        foreach ($vendorIds->filter()->unique() as $vendorId) {
            $this->forgetVendorDashboard((int) $vendorId);
        }
    }

    public function forgetCategoryIndex(): void
    {
        Cache::forget('catalog:categories:index');
    }

    public function bumpRecommendationVersion(): int
    {
        $currentVersion = Cache::get('recommendations.version');

        if ($currentVersion === null) {
            Cache::forever('recommendations.version', 2);

            return 2;
        }

        return (int) Cache::increment('recommendations.version');
    }
}
