<?php

namespace Tests\Feature\Payment;

use App\Enums\CommissionScope;
use App\Enums\PaymentStatus;
use App\Enums\PaymentVerificationStatus;
use App\Enums\ProductStatus;
use App\Enums\VendorApprovalStatus;
use App\Models\Address;
use App\Models\Category;
use App\Models\Commission;
use App\Models\CustomerProfile;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EsewaPaymentFlowTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.esewa.secret_key' => '8gBm/:&EnhH.1/q(',
            'services.esewa.form_url' => 'https://rc-epay.esewa.com.np/api/epay/main/v2/form',
            'services.esewa.status_check_url' => 'https://rc.esewa.com.np/api/epay/transaction/status',
            'services.esewa.merchant_code' => 'EPAYTEST',
            'services.esewa.success_url' => 'http://multi-vendor.localhost/api/payments/esewa/success',
            'services.esewa.failure_url' => 'http://multi-vendor.localhost/api/payments/esewa/failure',
        ]);
    }

    public function test_customer_can_initiate_esewa_payment_and_terminal_attempt_creates_new_attempt(): void
    {
        $prepared = $this->prepareOrderForPayment();
        $oldPayment = $prepared['payment'];
        $customer = $prepared['customer'];
        $order = $prepared['order'];

        $oldPayment->update([
            'status' => PaymentStatus::Failed,
            'verification_status' => PaymentVerificationStatus::Failed,
        ]);

        Sanctum::actingAs($customer);

        $response = $this->postJson('/api/payments/esewa/initiate', [
            'order_number' => $order->order_number,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('order_number', $order->order_number)
            ->assertJsonPath('payment.status', PaymentStatus::Initiated->value)
            ->assertJsonPath('esewa.gateway', 'esewa')
            ->assertJsonPath('esewa.method', 'POST')
            ->assertJsonPath('esewa.fields.product_code', 'EPAYTEST');

        $newPayment = $order->payments()->latest('id')->firstOrFail();

        $this->assertNotSame($oldPayment->id, $newPayment->id);
        $this->assertNotSame($oldPayment->transaction_uuid, $newPayment->transaction_uuid);
        $this->assertDatabaseCount('payments', 2);
        $this->assertSame($newPayment->transaction_uuid, data_get($newPayment->raw_request_json, 'latest.fields.transaction_uuid'));
    }

    public function test_success_callback_verifies_payment_and_duplicate_callback_is_idempotent(): void
    {
        Http::fake([
            'https://rc.esewa.com.np/api/epay/transaction/status*' => Http::response([
                'status' => 'COMPLETE',
                'totalAmount' => 200.0,
                'refId' => 'REF-1001',
            ]),
        ]);

        $prepared = $this->prepareOrderForPayment(price: 100.00, quantity: 2, stock: 5);
        $order = $prepared['order'];
        $payment = $prepared['payment'];
        $product = $prepared['product'];

        $encodedData = $this->encodeCallbackPayload($payment, 'COMPLETE', '200.00', 'TXN-1001');

        $this->getJson('/api/payments/esewa/success?order_number='.$order->order_number.'&data='.urlencode($encodedData))
            ->assertOk()
            ->assertJsonPath('verification.status', 'paid')
            ->assertJsonPath('verification.verified', true)
            ->assertJsonPath('payment.status', PaymentStatus::Paid->value)
            ->assertJsonPath('payment.verification_status', PaymentVerificationStatus::Verified->value)
            ->assertJsonPath('order.payment_status', PaymentStatus::Paid->value);

        $payment->refresh();
        $product->refresh();

        $this->assertSame('REF-1001', $payment->gateway_reference);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame(3, $product->stock_quantity);

        $this->getJson('/api/payments/esewa/success?order_number='.$order->order_number.'&data='.urlencode($encodedData))
            ->assertOk()
            ->assertJsonPath('idempotent', true)
            ->assertJsonPath('payment.status', PaymentStatus::Paid->value);

        $product->refresh();
        $this->assertSame(3, $product->stock_quantity);
        $this->assertSame(1, $order->statusHistories()->where('status', 'paid')->count());
    }

    public function test_amount_mismatch_fails_safely_without_paid_order(): void
    {
        Http::fake([
            'https://rc.esewa.com.np/api/epay/transaction/status*' => Http::response([
                'status' => 'COMPLETE',
                'totalAmount' => 150.0,
                'refId' => 'REF-MISMATCH',
            ]),
        ]);

        $prepared = $this->prepareOrderForPayment(price: 100.00, quantity: 2, stock: 5);
        $customer = $prepared['customer'];
        $order = $prepared['order'];
        $payment = $prepared['payment'];
        $product = $prepared['product'];

        Sanctum::actingAs($customer);

        $this->postJson('/api/payments/esewa/verify', [
            'order_number' => $order->order_number,
            'transaction_uuid' => $payment->transaction_uuid,
        ])
            ->assertOk()
            ->assertJsonPath('verification.status', 'mismatch')
            ->assertJsonPath('payment.status', PaymentStatus::Failed->value)
            ->assertJsonPath('payment.verification_status', PaymentVerificationStatus::Mismatch->value)
            ->assertJsonPath('order.payment_status', PaymentStatus::Failed->value);

        $product->refresh();
        $this->assertSame(5, $product->stock_quantity);
    }

    public function test_invalid_transaction_uuid_is_rejected_safely(): void
    {
        Http::fake();

        $prepared = $this->prepareOrderForPayment();
        $customer = $prepared['customer'];
        $order = $prepared['order'];
        $payment = $prepared['payment'];

        Sanctum::actingAs($customer);

        $this->postJson('/api/payments/esewa/verify', [
            'order_number' => $order->order_number,
            'transaction_uuid' => 'wrong-uuid',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('transaction_uuid');

        $payment->refresh();
        $this->assertSame(PaymentStatus::Initiated, $payment->status);
        $this->assertSame(PaymentVerificationStatus::Pending, $payment->verification_status);
        Http::assertNothingSent();
    }

    public function test_gateway_connection_failure_returns_pending_review_instead_of_throwing(): void
    {
        Http::fake(function (): void {
            throw new ConnectionException('DNS resolution failed for eSewa.');
        });

        $prepared = $this->prepareOrderForPayment(price: 1000.00, quantity: 2, stock: 5);
        $customer = $prepared['customer'];
        $order = $prepared['order'];
        $payment = $prepared['payment'];
        $product = $prepared['product'];

        Sanctum::actingAs($customer);

        $this->postJson('/api/payments/esewa/verify', [
            'order_number' => $order->order_number,
            'transaction_uuid' => $payment->transaction_uuid,
        ])
            ->assertOk()
            ->assertJsonPath('verification.status', 'pending_review')
            ->assertJsonPath('verification.verified', false)
            ->assertJsonPath('payment.status', PaymentStatus::PendingReview->value)
            ->assertJsonPath('payment.verification_status', PaymentVerificationStatus::Pending->value)
            ->assertJsonPath('order.payment_status', PaymentStatus::PendingReview->value);

        $product->refresh();
        $this->assertSame(5, $product->stock_quantity);
    }

    public function test_failure_callback_does_not_create_paid_order(): void
    {
        Http::fake([
            'https://rc.esewa.com.np/api/epay/transaction/status*' => Http::response([
                'status' => 'CANCELED',
                'totalAmount' => 200.0,
                'refId' => 'REF-CANCELLED',
            ]),
        ]);

        $prepared = $this->prepareOrderForPayment(price: 100.00, quantity: 2, stock: 5);
        $order = $prepared['order'];
        $payment = $prepared['payment'];
        $product = $prepared['product'];

        $this->getJson('/api/payments/esewa/failure?order_number='.$order->order_number.'&transaction_uuid='.$payment->transaction_uuid)
            ->assertOk()
            ->assertJsonPath('verification.status', 'cancelled')
            ->assertJsonPath('payment.status', PaymentStatus::Cancelled->value)
            ->assertJsonPath('order.payment_status', PaymentStatus::Cancelled->value);

        $product->refresh();
        $this->assertSame(5, $product->stock_quantity);
    }

    public function test_verified_payment_with_insufficient_stock_fails_safely_without_overselling(): void
    {
        Http::fake([
            'https://uat.esewa.com.np/api/epay/transaction/status*' => Http::response([
                'status' => 'COMPLETE',
                'totalAmount' => 100.0,
                'refId' => 'REF-OVERSELL',
            ]),
        ]);

        $prepared = $this->prepareOrderForPayment(price: 100.00, quantity: 1, stock: 1);
        $customer = $prepared['customer'];
        $order = $prepared['order'];
        $payment = $prepared['payment'];
        $product = $prepared['product'];

        $product->update([
            'stock_quantity' => 0,
        ]);

        Sanctum::actingAs($customer);

        $this->postJson('/api/payments/esewa/verify', [
            'order_number' => $order->order_number,
            'transaction_uuid' => $payment->transaction_uuid,
        ])
            ->assertOk()
            ->assertJsonPath('verification.status', 'failed')
            ->assertJsonPath('verification.verified', false)
            ->assertJsonPath('payment.status', PaymentStatus::Failed->value)
            ->assertJsonPath('payment.verification_status', PaymentVerificationStatus::Failed->value)
            ->assertJsonPath('order.payment_status', PaymentStatus::Failed->value);

        $product->refresh();
        $this->assertSame(0, $product->stock_quantity);
    }

    public function test_status_endpoint_reflects_backend_state_and_enforces_ownership(): void
    {
        $prepared = $this->prepareOrderForPayment();
        $customer = $prepared['customer'];
        $order = $prepared['order'];

        Sanctum::actingAs($customer);

        $this->getJson('/api/payments/'.$order->order_number.'/status')
            ->assertOk()
            ->assertJsonPath('order.order_number', $order->order_number)
            ->assertJsonPath('payment.status', PaymentStatus::Initiated->value)
            ->assertJsonPath('verification.status', PaymentVerificationStatus::Pending->value);

        $intruder = User::factory()->customer()->create();
        CustomerProfile::factory()->for($intruder)->create();

        Sanctum::actingAs($intruder);

        $this->getJson('/api/payments/'.$order->order_number.'/status')
            ->assertForbidden()
            ->assertJson([
                'message' => 'This action is unauthorized.',
            ]);
    }

    private function prepareOrderForPayment(float $price = 100.00, int $quantity = 2, int $stock = 10): array
    {
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
                'price' => $price,
                'discount_price' => null,
                'stock_quantity' => $stock,
            ]);

        Sanctum::actingAs($customer);

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => $quantity,
        ])->assertOk();

        $checkoutResponse = $this->postJson('/api/checkout', [
            'address_id' => $address->id,
        ])->assertOk();

        $order = Order::query()
            ->where('order_number', $checkoutResponse->json('order.order_number'))
            ->firstOrFail();

        return [
            'customer' => $customer,
            'address' => $address,
            'vendor' => $vendor,
            'product' => $product,
            'order' => $order,
            'payment' => $order->payments()->latest('id')->firstOrFail(),
        ];
    }

    private function encodeCallbackPayload(
        Payment $payment,
        string $status,
        string $totalAmount,
        string $transactionCode,
    ): string {
        $payload = [
            'status' => $status,
            'transaction_code' => $transactionCode,
            'total_amount' => (float) $totalAmount,
            'transaction_uuid' => $payment->transaction_uuid,
            'product_code' => 'EPAYTEST',
            'signed_field_names' => 'transaction_code,status,total_amount,transaction_uuid,product_code,signed_field_names',
        ];

        $payload['signature'] = $this->generateEsewaSignature($payload, $payload['signed_field_names']);

        return base64_encode((string) json_encode($payload));
    }

    private function generateEsewaSignature(array $payload, string $signedFieldNames): string
    {
        $message = collect(explode(',', $signedFieldNames))
            ->map(fn (string $field): string => trim($field))
            ->filter()
            ->map(fn (string $field): string => $field.'='.(string) ($payload[$field] ?? ''))
            ->implode(',');

        return base64_encode(hash_hmac('sha256', $message, (string) config('services.esewa.secret_key'), true));
    }
}
