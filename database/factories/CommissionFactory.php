<?php

namespace Database\Factories;

use App\Enums\CommissionScope;
use App\Models\Commission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Commission>
 */
class CommissionFactory extends Factory
{
    protected $model = Commission::class;

    public function definition(): array
    {
        return [
            'scope' => CommissionScope::Global,
            'vendor_id' => null,
            'rate' => fake()->randomFloat(2, 1, 20),
            'active_from' => now(),
            'active_to' => null,
        ];
    }

    public function vendorSpecific(): static
    {
        return $this->state(fn (array $attributes) => [
            'scope' => CommissionScope::VendorSpecific,
            'vendor_id' => User::factory()->vendor(),
        ]);
    }
}
