<?php

namespace Database\Factories;

use App\Enums\VendorOrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Models\VendorOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorOrder>
 */
class VendorOrderFactory extends Factory
{
    protected $model = VendorOrder::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 20, 300);
        $commission = fake()->randomFloat(2, 1, 20);

        return [
            'order_id' => Order::factory(),
            'vendor_id' => User::factory()->vendor(),
            'subtotal' => $subtotal,
            'commission_amount' => $commission,
            'net_amount' => $subtotal - $commission,
            'status' => VendorOrderStatus::New,
        ];
    }
}
