<?php

namespace Tests\Feature\Customer;

use App\Enums\UserRole;
use App\Models\Address;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerProfileAndAddressTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_customer_can_view_and_update_profile(): void
    {
        $customer = User::factory()->customer()->create([
            'name' => 'Original Name',
            'phone' => '+9779800000005',
        ]);
        CustomerProfile::factory()->for($customer)->create();

        Sanctum::actingAs($customer);

        $this->getJson('/api/customer/profile')
            ->assertOk()
            ->assertJsonPath('user.name', 'Original Name')
            ->assertJsonPath('user.role', UserRole::Customer->value);

        $this->putJson('/api/customer/profile', [
            'name' => 'Updated Name',
            'phone' => '+9779800000006',
        ])
            ->assertOk()
            ->assertJsonPath('user.name', 'Updated Name')
            ->assertJsonPath('user.phone', '+9779800000006');

        $this->assertDatabaseHas('users', [
            'id' => $customer->id,
            'name' => 'Updated Name',
            'phone' => '+9779800000006',
        ]);
    }

    public function test_customer_can_create_list_update_and_delete_addresses_with_default_handling(): void
    {
        $customer = User::factory()->customer()->create();
        $profile = CustomerProfile::factory()->for($customer)->create();

        Sanctum::actingAs($customer);

        $firstAddressResponse = $this->postJson('/api/customer/addresses', [
            'full_name' => 'Home User',
            'phone' => '+9779800000010',
            'address_line_1' => 'Baneshwor',
            'city' => 'Kathmandu',
            'district' => 'Kathmandu',
        ]);

        $firstAddressId = $firstAddressResponse->json('id');

        $firstAddressResponse
            ->assertOk()
            ->assertJsonPath('is_default', true);

        $this->assertDatabaseHas('customer_profiles', [
            'id' => $profile->id,
            'default_address_id' => $firstAddressId,
        ]);

        $secondAddressResponse = $this->postJson('/api/customer/addresses', [
            'full_name' => 'Office User',
            'phone' => '+9779800000011',
            'address_line_1' => 'Putalisadak',
            'city' => 'Kathmandu',
            'district' => 'Kathmandu',
            'is_default' => true,
        ]);

        $secondAddressId = $secondAddressResponse->json('id');

        $secondAddressResponse
            ->assertOk()
            ->assertJsonPath('is_default', true);

        $this->assertDatabaseHas('addresses', [
            'id' => $firstAddressId,
            'is_default' => false,
        ]);
        $this->assertDatabaseHas('customer_profiles', [
            'id' => $profile->id,
            'default_address_id' => $secondAddressId,
        ]);

        $this->getJson('/api/customer/addresses')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->putJson("/api/customer/addresses/{$firstAddressId}", [
            'city' => 'Lalitpur',
            'is_default' => true,
        ])
            ->assertOk()
            ->assertJsonPath('city', 'Lalitpur')
            ->assertJsonPath('is_default', true);

        $this->assertDatabaseHas('customer_profiles', [
            'id' => $profile->id,
            'default_address_id' => $firstAddressId,
        ]);

        $this->putJson('/api/customer/profile', [
            'default_address_id' => $secondAddressId,
        ])
            ->assertOk()
            ->assertJsonPath('default_address_id', $secondAddressId);

        $this->deleteJson("/api/customer/addresses/{$secondAddressId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('addresses', [
            'id' => $secondAddressId,
        ]);
    }

    public function test_customer_cannot_mutate_another_customers_address(): void
    {
        $owner = User::factory()->customer()->create();
        CustomerProfile::factory()->for($owner)->create();
        $address = Address::factory()->for($owner)->create();

        $intruder = User::factory()->customer()->create();
        CustomerProfile::factory()->for($intruder)->create();

        Sanctum::actingAs($intruder);

        $this->putJson("/api/customer/addresses/{$address->id}", [
            'city' => 'Pokhara',
        ])
            ->assertForbidden()
            ->assertJson([
                'message' => 'This action is unauthorized.',
            ]);

        $this->deleteJson("/api/customer/addresses/{$address->id}")
            ->assertForbidden();
    }

    public function test_admin_is_blocked_from_customer_only_routes_by_role_middleware(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $this->getJson('/api/customer/profile')
            ->assertForbidden()
            ->assertJson([
                'message' => 'This action is unauthorized.',
            ]);
    }
}
