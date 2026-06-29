<?php

namespace Tests\Feature\Admin;

use App\Enums\CommissionScope;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\VendorApprovalStatus;
use App\Models\Address;
use App\Models\Category;
use App\Models\Commission;
use App\Models\CustomerProfile;
use App\Models\Order;
use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportingAndSettingsTest extends TestCase
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

    public function test_admin_reporting_and_settings_endpoints_are_admin_only_and_match_db(): void
    {
        $prepared = $this->preparePaidVendorOrder();
        $admin = User::factory()->admin()->create();
        $customer = $prepared['customer'];
        $vendor = $prepared['vendor'];

        Sanctum::actingAs($customer);
        $this->getJson('/api/admin/dashboard')->assertForbidden();

        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('metrics.total_users', 3)
            ->assertJsonPath('metrics.total_vendors', 1)
            ->assertJsonPath('metrics.total_customers', 1)
            ->assertJsonPath('metrics.approved_vendors', 1)
            ->assertJsonPath('metrics.total_orders', 1)
            ->assertJsonPath('metrics.paid_orders', 1)
            ->assertJsonPath('metrics.failed_orders', 0)
            ->assertJsonPath('metrics.cancelled_orders', 0)
            ->assertJsonPath('metrics.gross_revenue', '100.00')
            ->assertJsonPath('metrics.commission_earned', '10.00')
            ->assertJsonPath('top_vendors.0.vendor_id', $vendor->id);

        $this->getJson('/api/admin/reports')
            ->assertOk()
            ->assertJsonPath('summary.orders_count', 1)
            ->assertJsonPath('summary.paid_orders_count', 1)
            ->assertJsonPath('summary.payment_success_count', 1)
            ->assertJsonPath('summary.payment_failure_count', 0)
            ->assertJsonPath('summary.gross_revenue', '100.00')
            ->assertJsonPath('summary.commission_earned', '10.00')
            ->assertJsonPath('top_products.0.quantity_sold', 1)
            ->assertJsonPath('top_vendors.0.vendor_id', $vendor->id);

        $this->getJson('/api/admin/users')
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $this->getJson('/api/admin/settings')
            ->assertOk()
            ->assertJsonFragment(['key' => 'platform.name']);

        $this->getJson('/api/admin/settings')
            ->assertOk()
            ->assertJsonFragment(['key' => 'platform.name']);

        $this->putJson('/api/admin/settings', [
            'settings' => [
                [
                    'key' => 'platform.support_email',
                    'value' => ['email' => 'ops@example.com'],
                ],
            ],
        ])
            ->assertOk()
            ->assertJsonFragment(['key' => 'platform.support_email']);

        $this->assertDatabaseHas('platform_settings', [
            'key' => 'platform.support_email',
        ]);

        $this->getJson('/api/admin/commissions')
            ->assertOk()
            ->assertJsonPath('global_rate', '10.00');

        $this->putJson('/api/admin/commissions', [
            'global_rate' => 12.5,
            'vendor_overrides' => [
                [
                    'vendor_id' => $vendor->id,
                    'rate' => 7.5,
                ],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('global_rate', '12.50')
            ->assertJsonPath('vendor_overrides.0.vendor_id', $vendor->id)
            ->assertJsonPath('vendor_overrides.0.rate', '7.50');

        $this->assertSame('7.50', $vendor->vendorProfile()->firstOrFail()->commission_rate_override);
    }

    public function test_vendor_dashboard_and_sales_report_are_scoped_to_authenticated_vendor(): void
    {
        $prepared = $this->preparePaidVendorOrder();
        $vendor = $prepared['vendor'];
        $customer = $prepared['customer'];

        Sanctum::actingAs($customer);
        $this->getJson('/api/vendor/dashboard')->assertForbidden();

        Sanctum::actingAs($vendor);

        $this->getJson('/api/vendor/dashboard')
            ->assertOk()
            ->assertJsonPath('metrics.products_count', 1)
            ->assertJsonPath('metrics.vendor_orders_count', 1)
            ->assertJsonPath('metrics.paid_vendor_orders_count', 1)
            ->assertJsonPath('metrics.gross_sales', '100.00')
            ->assertJsonPath('metrics.net_sales', '90.00')
            ->assertJsonPath('best_selling_products.0.quantity_sold', 1);

        $this->getJson('/api/vendor/reports/sales')
            ->assertOk()
            ->assertJsonPath('summary.vendor_orders_count', 1)
            ->assertJsonPath('summary.gross_sales', '100.00')
            ->assertJsonPath('summary.commission_total', '10.00')
            ->assertJsonPath('summary.net_sales', '90.00')
            ->assertJsonPath('orders.data.0.vendor_id', $vendor->id)
            ->assertJsonPath('orders.meta.current_page', 1)
            ->assertJsonPath('orders.meta.total', 1)
            ->assertJsonPath('orders.links.first', url('/api/vendor/reports/sales?page=1'))
            ->assertJsonPath('orders.data.0.order.customer.id', $prepared['customer']->id)
            ->assertJsonPath('orders.data.0.order.payment.status', PaymentStatus::Paid->value);
    }

    private function preparePaidVendorOrder(): array
    {
        Http::fake([
            'https://uat.esewa.com.np/api/epay/transaction/status*' => Http::response([
                'status' => 'COMPLETE',
                'totalAmount' => 100.0,
                'refId' => 'REF-REPORT',
            ]),
        ]);

        Commission::factory()->create([
            'scope' => CommissionScope::Global,
            'vendor_id' => null,
            'rate' => 10.00,
        ]);

        PlatformSetting::query()->updateOrCreate(['key' => 'platform.name'], ['value' => ['name' => 'Multi Vendor Ecommerce']]);

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

        return compact('customer', 'vendor', 'order');
    }
}
