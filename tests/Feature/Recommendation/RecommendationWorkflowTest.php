<?php

namespace Tests\Feature\Recommendation;

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
use Illuminate\Support\Str;
use Tests\TestCase;

class RecommendationWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_recommendation_command_generates_rows_and_endpoint_returns_safe_results(): void
    {
        [$baseProduct, $relatedProduct] = $this->createPaidCoPurchaseData();

        Artisan::call('recommendations:generate', ['--limit' => 8]);

        $this->assertDatabaseHas('recommendations', [
            'product_id' => $baseProduct->id,
            'recommended_product_id' => $relatedProduct->id,
        ]);

        $this->getJson('/api/recommendations/'.$baseProduct->id)
            ->assertOk()
            ->assertJsonPath('data.0.product_id', $baseProduct->id)
            ->assertJsonPath('data.0.recommended_product_id', $relatedProduct->id)
            ->assertJsonPath('data.0.product.id', $relatedProduct->id);

        $this->getJson('/api/recommendations/'.$baseProduct->id)
            ->assertOk()
            ->assertJsonPath('data.0.product_id', $baseProduct->id)
            ->assertJsonPath('data.0.recommended_product_id', $relatedProduct->id)
            ->assertJsonPath('data.0.product.id', $relatedProduct->id);

        $orphan = Product::factory()->create([
            'status' => ProductStatus::Draft,
            'stock_quantity' => 0,
        ]);

        $this->getJson('/api/recommendations/'.$orphan->id)
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_pending_payment_maintenance_commands_run_safely(): void
    {
        $customer = User::factory()->customer()->create();
        CustomerProfile::factory()->for($customer)->create();
        $address = Address::factory()->for($customer)->create();

        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'address_id' => $address->id,
            'payment_status' => PaymentStatus::Initiated,
            'order_status' => OrderStatus::PaymentInitiated,
            'placed_at' => now()->subDays(2),
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        $order->payments()->create([
            'payment_method' => 'esewa',
            'gateway' => 'esewa',
            'amount' => '200.00',
            'transaction_uuid' => (string) Str::uuid(),
            'status' => PaymentStatus::Initiated,
            'verification_status' => 'pending',
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        $order->payments()->latest('id')->firstOrFail()->forceFill([
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ])->save();

        Artisan::call('payments:cleanup-stale-pending', ['--hours' => 24]);
        Artisan::call('payments:reconcile-pending', ['--hours' => 1, '--limit' => 10]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => PaymentStatus::Failed->value,
        ]);
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

        foreach (range(1, 3) as $index) {
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
