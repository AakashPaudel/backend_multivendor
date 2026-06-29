<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        $price = fake()->randomFloat(2, 10, 500);

        return [
            'vendor_id' => User::factory()->vendor(),
            'category_id' => Category::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'sku' => strtoupper(fake()->bothify('SKU-####??')),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'price' => $price,
            'discount_price' => $price - fake()->randomFloat(2, 0, 5),
            'stock_quantity' => fake()->numberBetween(0, 100),
            'status' => ProductStatus::Draft,
            'weight' => fake()->randomFloat(2, 0.1, 10),
            'meta_title' => Str::title($name),
            'meta_description' => fake()->sentence(),
        ];
    }
}
