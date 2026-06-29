<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Enums\PaymentVerificationStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_method' => 'esewa',
            'gateway' => 'esewa',
            'amount' => fake()->randomFloat(2, 50, 500),
            'transaction_uuid' => (string) Str::uuid(),
            'gateway_reference' => null,
            'status' => PaymentStatus::Pending,
            'verification_status' => PaymentVerificationStatus::Pending,
            'raw_request_json' => ['provider' => 'esewa'],
            'raw_response_json' => null,
            'paid_at' => null,
        ];
    }
}
