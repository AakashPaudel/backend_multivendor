<?php

namespace Tests\Feature\Cache;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\VendorApprovalStatus;
use App\Models\Address;
use App\Models\CustomerProfile;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorOrder;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CacheInvalidationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_dashboard_cache_refreshes_after_user_changes(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('metrics.total_users', 1)
            ->assertJsonPath('metrics.total_customers', 0);

        User::factory()->customer()->create();

        $this->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('metrics.total_users', 2)
            ->assertJsonPath('metrics.total_customers', 1);
    }

    public function test_vendor_dashboard_cache_refreshes_after_product_changes(): void
    {
        $vendor = User::factory()->vendor()->create();
        VendorProfile::factory()->for($vendor)->create([
            'approval_status' => VendorApprovalStatus::Approved,
        ]);

        Sanctum::actingAs($vendor);

        $this->getJson('/api/vendor/dashboard')
            ->assertOk()
            ->assertJsonPath('metrics.products_count', 0)
            ->assertJsonPath('metrics.low_stock_products_count', 0);

        $product = Product::factory()->for($vendor, 'vendor')->create([
            'status' => ProductStatus::Active,
            'stock_quantity' => 10,
        ]);

        $this->getJson('/api/vendor/dashboard')
            ->assertOk()
            ->assertJsonPath('metrics.products_count', 1)
            ->assertJsonPath('metrics.low_stock_products_count', 0);

        $product->update([
            'stock_quantity' => 3,
        ]);

        $this->getJson('/api/vendor/dashboard')
            ->assertOk()
            ->assertJsonPath('metrics.products_count', 1)
            ->assertJsonPath('metrics.low_stock_products_count', 1);
    }

    public function test_recommendation_cache_refreshes_after_related_product_changes(): void
    {
        [$baseProduct, $relatedProduct] = $this->createPaidCoPurchaseData();

        Artisan::call('recommendations:generate', ['--limit' => 8]);

        $this->getJson('/api/recommendations/'.$baseProduct->id)
            ->assertOk()
            ->assertJsonPath('data.0.product.name', $relatedProduct->name);

        $relatedProduct->update([
            'name' => 'Updated Recommended Product',
        ]);

        $this->getJson('/api/recommendations/'.$baseProduct->id)
            ->assertOk()
            ->assertJsonPath('data.0.product.name', 'Updated Recommended Product');
    }

    private function createPaidCoPurchaseData(): array
    {
        $vendor = User::factory()->vendor()->create();
        VendorProfile::factory()->for($vendor)->create([
            'approval_status' => VendorApprovalStatus::Approved,
        ]);

        $baseProduct = Product::factory()->for($vendor, 'vendor')->create([
            'status' => ProductStatus::Active,
            'stock_quantity' => 20,
        ]);

        $relatedProduct = Product::factory()->for($vendor, 'vendor')->create([
            'status' => ProductStatus::Active,
            'stock_quantity' => 20,
        ]);

        foreach (range(1, 2) as $index) {
            $customer = User::factory()->customer()->create();
            CustomerProfile::factory()->for($customer)->create();
            $address = Address::factory()->for($customer)->create();

            $order = Order::factory()->create([
                'user_id' => $customer->id,
                'address_id' => $address->id,
                'payment_status' => PaymentStatus::Paid,
                'order_status' => OrderStatus::Paid,
                'placed_at' => now()->subDays($index),
            ]);

            $vendorOrder = VendorOrder::factory()->create([
                'order_id' => $order->id,
                'vendor_id' => $vendor->id,
            ]);

            OrderItem::factory()->create([
                'order_id' => $order->id,
                'vendor_order_id' => $vendorOrder->id,
                'product_id' => $baseProduct->id,
                'vendor_id' => $vendor->id,
                'product_name_snapshot' => $baseProduct->name,
                'sku_snapshot' => $baseProduct->sku,
            ]);

            OrderItem::factory()->create([
                'order_id' => $order->id,
                'vendor_order_id' => $vendorOrder->id,
                'product_id' => $relatedProduct->id,
                'vendor_id' => $vendor->id,
                'product_name_snapshot' => $relatedProduct->name,
                'sku_snapshot' => $relatedProduct->sku,
            ]);
        }

        return [$baseProduct, $relatedProduct];
    }
}
