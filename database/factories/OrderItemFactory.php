<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\VendorOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 4);
        $unitPrice = fake()->randomFloat(2, 10, 150);
        $lineTotal = $quantity * $unitPrice;
        $commission = fake()->randomFloat(2, 1, 10);

        return [
            'order_id' => Order::factory(),
            'vendor_order_id' => VendorOrder::factory(),
            'product_id' => Product::factory(),
            'vendor_id' => fn (array $attributes) => Product::find($attributes['product_id'])?->vendor_id ?? Product::factory()->create()->vendor_id,
            'product_name_snapshot' => fake()->words(3, true),
            'sku_snapshot' => strtoupper(fake()->bothify('SKU-####??')),
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'line_total' => $lineTotal,
            'commission_amount' => $commission,
            'net_amount' => $lineTotal - $commission,
            'status' => fake()->randomElement(['new', 'accepted', 'packed']),
        ];
    }
}
