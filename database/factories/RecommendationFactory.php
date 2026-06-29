<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Recommendation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recommendation>
 */
class RecommendationFactory extends Factory
{
    protected $model = Recommendation::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'recommended_product_id' => Product::factory(),
            'support_value' => fake()->randomFloat(4, 0, 1),
            'confidence_value' => fake()->randomFloat(4, 0, 1),
            'lift_value' => fake()->randomFloat(4, 0, 3),
            'generated_at' => now(),
        ];
    }
}
