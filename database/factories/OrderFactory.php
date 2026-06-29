<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 50, 500);
        $discount = fake()->randomFloat(2, 0, 20);
        $shipping = fake()->randomFloat(2, 0, 15);
        $tax = fake()->randomFloat(2, 0, 25);

        return [
            'order_number' => 'ORD-'.Str::upper(Str::random(10)),
            'user_id' => User::factory()->customer(),
            'address_id' => Address::factory(),
            'subtotal' => $subtotal,
            'discount_total' => $discount,
            'shipping_total' => $shipping,
            'tax_total' => $tax,
            'grand_total' => $subtotal - $discount + $shipping + $tax,
            'payment_status' => PaymentStatus::Pending,
            'order_status' => OrderStatus::PendingPayment,
            'notes' => fake()->sentence(),
            'placed_at' => now(),
        ];
    }
}
