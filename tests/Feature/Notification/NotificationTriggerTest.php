<?php

namespace Tests\Feature\Notification;

use App\Enums\CommissionScope;
use App\Enums\ProductStatus;
use App\Enums\VendorApprovalStatus;
use App\Jobs\GenerateRecommendationsJob;
use App\Models\Address;
use App\Models\Category;
use App\Models\Commission;
use App\Models\CustomerProfile;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTriggerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.esewa.secret_key' => '8gBm/:&EnhH.1/q(',
            'services.esewa.status_check_url' => 'https://uat.esewa.com.np/api/epay/transaction/status',
            'services.esewa.merchant_code' => 'EPAYTEST',
        ]);
    }

    public function test_vendor_approval_queues_notification(): void
    {
        Queue::fake();

        $admin = User::factory()->admin()->create();
        $vendor = User::factory()->vendor()->create();
        $profile = VendorProfile::factory()->for($vendor)->create([
            'approval_status' => VendorApprovalStatus::Pending,
        ]);

        Sanctum::actingAs($admin);

        $this->putJson('/api/admin/vendors/'.$profile->id.'/approve')
            ->assertOk()
            ->assertJsonPath('approval_status', 'approved');

        Queue::assertPushed(SendQueuedNotifications::class);
    }

    public function test_checkout_and_payment_verification_queue_notifications_and_recommendation_job(): void
    {
        Queue::fake();

        Http::fake([
            'https://uat.esewa.com.np/api/epay/transaction/status*' => Http::response([
                'status' => 'COMPLETE',
                'totalAmount' => 100.0,
                'refId' => 'REF-NOTIFY',
            ]),
        ]);

        Commission::factory()->create([
            'scope' => CommissionScope::Global,
            'vendor_id' => null,
            'rate' => 10.00,
        ]);

        $customer = User::factory()->customer()->create();
        CustomerProfile::factory()->for($customer)->create();
        $address = Address::factory()->for($customer)->create();

        $vendor = User::factory()->vendor()->create();
        VendorProfile::factory()->for($vendor)->create([
            'approval_status' => VendorApprovalStatus::Approved,
        ]);

        $product = Product::factory()
            ->for($vendor, 'vendor')
            ->for(Category::factory()->create(['is_active' => true]))
            ->create([
                'status' => ProductStatus::Active,
                'price' => 100.00,
                'discount_price' => null,
                'stock_quantity' => 10,
            ]);

        Sanctum::actingAs($customer);

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertOk();

        $checkoutResponse = $this->postJson('/api/checkout', [
            'address_id' => $address->id,
        ])->assertOk();

        Queue::assertPushed(SendQueuedNotifications::class);

        $order = Order::query()
            ->where('order_number', $checkoutResponse->json('order.order_number'))
            ->firstOrFail();

        $payment = $order->payments()->latest('id')->firstOrFail();

        $this->postJson('/api/payments/esewa/verify', [
            'order_number' => $order->order_number,
            'transaction_uuid' => $payment->transaction_uuid,
        ])->assertOk();

        Queue::assertPushed(GenerateRecommendationsJob::class);
        Queue::assertPushedTimes(SendQueuedNotifications::class, 4);
    }
}
