<?php

namespace Tests\Feature\Order;

use App\Enums\CommissionScope;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\VendorApprovalStatus;
use App\Models\Address;
use App\Models\Category;
use App\Models\Commission;
use App\Models\CustomerProfile;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderManagementTest extends TestCase
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

    public function test_customer_can_list_show_and_download_own_invoice(): void
    {
        Storage::fake('local');

        $prepared = $this->preparePaidOrder();
        $customer = $prepared['customer'];
        $order = $prepared['order'];

        Sanctum::actingAs($customer);

        $this->getJson('/api/orders')
            ->assertOk()
            ->assertJsonPath('data.0.order_number', $order->order_number)
            ->assertJsonPath('data.0.payment_status', PaymentStatus::Paid->value);

        $this->getJson('/api/orders/'.$order->order_number)
            ->assertOk()
            ->assertJsonPath('order_number', $order->order_number)
            ->assertJsonPath('payment_status', PaymentStatus::Paid->value)
            ->assertJsonCount(1, 'vendor_orders')
            ->assertJsonCount(1, 'items');

        $invoiceResponse = $this->get('/api/orders/'.$order->order_number.'/invoice');

        $invoiceResponse->assertOk();
        $invoiceResponse->assertHeader('content-type', 'text/html; charset=UTF-8');
        Storage::disk('local')->assertExists('invoices/orders/'.$order->order_number.'.html');

        $intruder = User::factory()->customer()->create();
        CustomerProfile::factory()->for($intruder)->create();

        Sanctum::actingAs($intruder);

        $this->getJson('/api/orders/'.$order->order_number)
            ->assertForbidden();

        $this->get('/api/orders/'.$order->order_number.'/invoice')
            ->assertForbidden();
    }

    public function test_vendor_can_view_own_vendor_orders_and_update_statuses(): void
    {
        $prepared = $this->preparePaidOrder();
        $vendor = $prepared['vendor'];
        $vendorOrderId = $prepared['vendor_order_id'];
        $orderNumber = $prepared['order']->order_number;

        Sanctum::actingAs($vendor);

        $this->getJson('/api/vendor/orders')
            ->assertOk()
            ->assertJsonPath('data.0.id', $vendorOrderId);

        $this->getJson('/api/vendor/orders/'.$vendorOrderId)
            ->assertOk()
            ->assertJsonPath('id', $vendorOrderId)
            ->assertJsonPath('order.order_number', $orderNumber);

        $this->putJson('/api/vendor/orders/'.$vendorOrderId.'/status', [
            'status' => 'accepted',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'accepted');

        $this->assertDatabaseHas('orders', [
            'order_number' => $orderNumber,
            'order_status' => 'processing',
        ]);

        $this->putJson('/api/vendor/orders/'.$vendorOrderId.'/status', [
            'status' => 'packed',
        ])->assertOk();

        $this->putJson('/api/vendor/orders/'.$vendorOrderId.'/status', [
            'status' => 'shipped',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'shipped');

        $this->assertDatabaseHas('orders', [
            'order_number' => $orderNumber,
            'order_status' => 'partially_shipped',
        ]);
    }

    public function test_admin_can_list_show_and_override_order_status_with_audit_log(): void
    {
        $prepared = $this->preparePaidOrder();
        $admin = User::factory()->admin()->create();
        $order = $prepared['order'];

        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/orders')
            ->assertOk()
            ->assertJsonPath('data.0.id', $order->id);

        $this->getJson('/api/admin/orders/'.$order->id)
            ->assertOk()
            ->assertJsonPath('id', $order->id)
            ->assertJsonPath('order_number', $order->order_number);

        $this->putJson('/api/admin/orders/'.$order->id.'/status', [
            'status' => 'completed',
            'message' => 'Admin override for testing.',
        ])
            ->assertOk()
            ->assertJsonPath('order_status', 'completed');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'order.admin_status_updated',
            'entity_type' => Order::class,
            'entity_id' => $order->id,
        ]);
    }

    private function preparePaidOrder(): array
    {
        Http::fake([
            'https://uat.esewa.com.np/api/epay/transaction/status*' => Http::response([
                'status' => 'COMPLETE',
                'totalAmount' => 100.0,
                'refId' => 'REF-PHASE8',
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

        $order = Order::query()
            ->where('order_number', $checkoutResponse->json('order.order_number'))
            ->firstOrFail();

        $payment = $order->payments()->latest('id')->firstOrFail();

        $this->postJson('/api/payments/esewa/verify', [
            'order_number' => $order->order_number,
            'transaction_uuid' => $payment->transaction_uuid,
        ])->assertOk();

        $order->refresh();

        return [
            'customer' => $customer,
            'vendor' => $vendor,
            'order' => $order,
            'vendor_order_id' => $order->vendorOrders()->firstOrFail()->id,
        ];
    }
}
