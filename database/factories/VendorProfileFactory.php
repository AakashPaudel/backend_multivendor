<?php

namespace Database\Factories;

use App\Enums\VendorApprovalStatus;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VendorProfile>
 */
class VendorProfileFactory extends Factory
{
    protected $model = VendorProfile::class;

    public function definition(): array
    {
        $storeName = fake()->unique()->company();

        return [
            'user_id' => User::factory()->vendor(),
            'store_name' => $storeName,
            'slug' => Str::slug($storeName).'-'.fake()->unique()->numberBetween(100, 999),
            'description' => fake()->sentence(),
            'business_email' => fake()->unique()->companyEmail(),
            'business_phone' => fake()->e164PhoneNumber(),
            'address_line' => fake()->streetAddress(),
            'city' => fake()->city(),
            'district' => fake()->citySuffix(),
            'country' => 'Nepal',
            'approval_status' => VendorApprovalStatus::Pending,
            'commission_rate_override' => null,
        ];
    }
}
