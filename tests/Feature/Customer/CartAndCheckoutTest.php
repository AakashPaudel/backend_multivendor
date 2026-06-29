<?php

namespace Tests\Feature\Customer;

use App\Enums\CommissionScope;
use App\Enums\ProductStatus;
use App\Enums\VendorApprovalStatus;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Commission;
use App\Models\CustomerProfile;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CartAndCheckoutTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_customer_can_manage_cart_and_totals_are_authoritative(): void
    {
        $customer = User::factory()->customer()->create();
        CustomerProfile::factory()->for($customer)->create();

        $product = $this->createSellableProduct([
            'price' => 100,
            'discount_price' => 80,
            'stock_quantity' => 10,
        ]);

        Sanctum::actingAs($customer);

        $addResponse = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $cartItemId = $addResponse->json('items.0.id');

        $addResponse
            ->assertOk()
            ->assertJsonPath('totals.item_count', 2)
            ->assertJsonPath('totals.distinct_items', 1)
            ->assertJsonPath('totals.vendor_count', 1)
            ->assertJsonPath('totals.grand_total', '160.00')
            ->assertJsonPath('items.0.current_unit_price', '80.00')
            ->assertJsonPath('items.0.is_available', true);

        $product->update([
            'price' => 120,
            'discount_price' => 110,
        ]);

        $this->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('items.0.current_unit_price', '110.00')
            ->assertJsonPath('totals.subtotal', '240.00')
            ->assertJsonPath('totals.discount_total', '20.00')
            ->assertJsonPath('totals.grand_total', '220.00');

        $this->putJson("/api/cart/items/{$cartItemId}", [
            'quantity' => 3,
        ])
            ->assertOk()
            ->assertJsonPath('totals.item_count', 3)
            ->assertJsonPath('totals.grand_total', '330.00');

        $this->deleteJson("/api/cart/items/{$cartItemId}")
            ->assertOk()
            ->assertJsonPath('totals.item_count', 0)
            ->assertJsonPath('totals.is_checkout_ready', false);

        $this->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('totals.item_count', 0)
            ->assertJsonPath('totals.is_checkout_ready', false)
            ->assertJsonCount(0, 'items');
    }

    public function test_cart_rejects_unsellable_products_and_excess_quantities(): void
    {
        $customer = User::factory()->customer()->create();
        CustomerProfile::factory()->for($customer)->create();

        $pendingVendor = User::factory()->vendor()->create();
        VendorProfile::factory()->for($pendingVendor)->create([
            'approval_status' => VendorApprovalStatus::Pending,
        ]);
        $pendingProduct = Product::factory()->for($pendingVendor, 'vendor')->create([
            'status' => ProductStatus::Active,
            'stock_quantity' => 5,
        ]);

        $lowStockProduct = $this->createSellableProduct([
            'stock_quantity' => 2,
        ]);

        Sanctum::actingAs($customer);

        $this->postJson('/api/cart/items', [
            'product_id' => $pendingProduct->id,
            'quantity' => 1,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('product_id');

        $this->postJson('/api/cart/items', [
            'product_id' => $lowStockProduct->id,
            'quantity' => 3,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');
    }

    public function test_customer_cannot_mutate_another_customers_cart_item(): void
    {
        $owner = User::factory()->customer()->create();
        CustomerProfile::factory()->for($owner)->create();

        $product = $this->createSellableProduct();
        $cart = Cart::factory()->for($owner)->create();
        $cartItem = $cart->items()->create([
            'product_id' => $product->id,
            'vendor_id' => $product->vendor_id,
            'quantity' => 1,
            'unit_price' => $product->currentUnitPrice(),
        ]);

        $intruder = User::factory()->customer()->create();
        CustomerProfile::factory()->for($intruder)->create();

        Sanctum::actingAs($intruder);

        $this->putJson("/api/cart/items/{$cartItem->id}", [
            'quantity' => 2,
        ])
            ->assertForbidden()
            ->assertJson([
                'message' => 'This action is unauthorized.',
            ]);

        $this->deleteJson("/api/cart/items/{$cartItem->id}")
            ->assertForbidden()
            ->assertJson([
                'message' => 'This action is unauthorized.',
            ]);
    }

    public function test_checkout_creates_pending_marketplace_records_for_multi_vendor_cart(): void
    {
        Commission::factory()->create([
            'scope' => CommissionScope::Global,
            'vendor_id' => null,
            'rate' => 10.00,
        ]);

        $customer = User::factory()->customer()->create();
        CustomerProfile::factory()->for($customer)->create();
        $address = Address::factory()->for($customer)->create();

        $productA = $this->createSellableProduct([
            'price' => 100,
            'discount_price' => 90,
            'stock_quantity' => 10,
        ]);
        $productB = $this->createSellableProduct([
            'price' => 200,
            'discount_price' => null,
            'stock_quantity' => 10,
        ]);

        Sanctum::actingAs($customer);

        $this->postJson('/api/cart/items', [
            'product_id' => $productA->id,
            'quantity' => 2,
        ])->assertOk();

        $this->postJson('/api/cart/items', [
            'product_id' => $productB->id,
            'quantity' => 1,
        ])->assertOk();

        $productA->update([
            'discount_price' => 85,
        ]);

        $response = $this->postJson('/api/checkout', [
            'address_id' => $address->id,
            'notes' => 'Please ring the bell.',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('order.payment_status', 'initiated')
            ->assertJsonPath('order.order_status', 'payment_initiated')
            ->assertJsonPath('totals.subtotal', '400.00')
            ->assertJsonPath('totals.discount_total', '30.00')
            ->assertJsonPath('totals.grand_total', '370.00')
            ->assertJsonCount(2, 'vendor_orders')
            ->assertJsonPath('payment.status', 'initiated')
            ->assertJsonPath('payment.verification_status', 'pending')
            ->assertJsonPath('payment_initiation.gateway', 'esewa')
            ->assertJsonPath('payment_initiation.amount', '370.00')
            ->assertJsonPath('payment_initiation.total_amount', '370.00');

        $orderNumber = $response->json('order.order_number');

        $order = Order::query()->where('order_number', $orderNumber)->firstOrFail();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'user_id' => $customer->id,
            'payment_status' => 'initiated',
            'order_status' => 'payment_initiated',
            'grand_total' => '370.00',
        ]);
        $this->assertDatabaseCount('vendor_orders', 2);
        $this->assertDatabaseCount('order_items', 2);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('order_status_histories', 3);
        $this->assertSame(0, Cart::query()->where('user_id', $customer->id)->firstOrFail()->items()->count());

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $productA->id,
            'unit_price' => '85.00',
            'line_total' => '170.00',
            'commission_amount' => '17.00',
            'net_amount' => '153.00',
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $productB->id,
            'unit_price' => '200.00',
            'line_total' => '200.00',
            'commission_amount' => '20.00',
            'net_amount' => '180.00',
        ]);

        $payment = Payment::query()->where('order_id', $order->id)->firstOrFail();

        $this->assertSame('370.00', $payment->amount);
        $this->assertSame($payment->transaction_uuid, data_get($payment->raw_request_json, 'transaction_uuid'));
    }

    public function test_checkout_requires_customer_owned_address_and_non_empty_cart(): void
    {
        $customer = User::factory()->customer()->create();
        CustomerProfile::factory()->for($customer)->create();

        $otherCustomer = User::factory()->customer()->create();
        CustomerProfile::factory()->for($otherCustomer)->create();
        $foreignAddress = Address::factory()->for($otherCustomer)->create();

        $product = $this->createSellableProduct();

        Sanctum::actingAs($customer);

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertOk();

        $this->postJson('/api/checkout', [
            'address_id' => $foreignAddress->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('address_id');

        Cart::query()->where('user_id', $customer->id)->firstOrFail()->items()->delete();

        $ownAddress = Address::factory()->for($customer)->create();

        $this->postJson('/api/checkout', [
            'address_id' => $ownAddress->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cart');
    }

    public function test_checkout_rejects_cart_when_stock_becomes_invalid(): void
    {
        $customer = User::factory()->customer()->create();
        CustomerProfile::factory()->for($customer)->create();
        $address = Address::factory()->for($customer)->create();
        $product = $this->createSellableProduct([
            'stock_quantity' => 5,
        ]);

        Sanctum::actingAs($customer);

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 3,
        ])->assertOk();

        $product->update([
            'stock_quantity' => 2,
        ]);

        $this->postJson('/api/checkout', [
            'address_id' => $address->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cart');
    }

    private function createSellableProduct(array $overrides = []): Product
    {
        $vendor = User::factory()->vendor()->create();
        VendorProfile::factory()->for($vendor)->create([
            'approval_status' => VendorApprovalStatus::Approved,
        ]);

        return Product::factory()->for($vendor, 'vendor')->create([
            'status' => ProductStatus::Active,
            'stock_quantity' => 10,
            ...$overrides,
        ]);
    }
}
