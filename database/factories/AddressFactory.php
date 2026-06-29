<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->customer(),
            'full_name' => fake()->name(),
            'phone' => fake()->e164PhoneNumber(),
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => fake()->secondaryAddress(),
            'city' => fake()->city(),
            'district' => fake()->citySuffix(),
            'province' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'country' => 'Nepal',
            'is_default' => false,
        ];
    }
}
