<?php

namespace Tests\Feature\Vendor;

use App\Enums\VendorApprovalStatus;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VendorManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_vendor_can_view_and_update_own_profile_with_public_media_paths(): void
    {
        Storage::fake('public');

        $vendor = User::factory()->vendor()->create();
        $profile = VendorProfile::factory()->for($vendor)->create([
            'approval_status' => VendorApprovalStatus::Pending,
        ]);

        Sanctum::actingAs($vendor);

        $this->getJson('/api/vendor/profile')
            ->assertOk()
            ->assertJsonPath('id', $profile->id)
            ->assertJsonPath('approval_status', VendorApprovalStatus::Pending->value);

        $response = $this->withHeader('Accept', 'application/json')->put('/api/vendor/profile', [
            'store_name' => 'Updated Store Name',
            'city' => 'Pokhara',
            'logo' => UploadedFile::fake()->image('logo.jpg'),
            'banner' => UploadedFile::fake()->image('banner.jpg'),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('store_name', 'Updated Store Name')
            ->assertJsonPath('city', 'Pokhara')
            ->assertJsonPath('approval_status', VendorApprovalStatus::Pending->value);

        $profile->refresh();

        $this->assertNotNull($profile->logo_path);
        $this->assertNotNull($profile->banner_path);
        Storage::disk('public')->assertExists($profile->logo_path);
        Storage::disk('public')->assertExists($profile->banner_path);
    }

    public function test_admin_can_list_and_transition_vendor_approval_states_with_audit_logs(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor = User::factory()->vendor()->create();
        $profile = VendorProfile::factory()->for($vendor)->create([
            'approval_status' => VendorApprovalStatus::Pending,
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/vendors')
            ->assertOk()
            ->assertJsonPath('data.0.id', $profile->id);

        $this->putJson("/api/admin/vendors/{$profile->id}/approve")
            ->assertOk()
            ->assertJsonPath('approval_status', VendorApprovalStatus::Approved->value);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'vendor.approval_status_changed',
            'entity_type' => VendorProfile::class,
            'entity_id' => $profile->id,
        ]);

        $this->putJson("/api/admin/vendors/{$profile->id}/reject", [
            'reason' => 'Incomplete business details',
        ])
            ->assertOk()
            ->assertJsonPath('approval_status', VendorApprovalStatus::Rejected->value)
            ->assertJsonPath('rejection_reason', 'Incomplete business details');

        $this->putJson("/api/admin/vendors/{$profile->id}/suspend", [
            'reason' => 'Policy violation',
        ])
            ->assertOk()
            ->assertJsonPath('approval_status', VendorApprovalStatus::Suspended->value)
            ->assertJsonPath('rejection_reason', 'Policy violation');
    }

    public function test_non_admin_cannot_access_admin_vendor_management_routes(): void
    {
        $vendor = User::factory()->vendor()->create();
        $profile = VendorProfile::factory()->for($vendor)->create();

        Sanctum::actingAs($vendor);

        $this->getJson('/api/admin/vendors')
            ->assertForbidden()
            ->assertJson([
                'message' => 'This action is unauthorized.',
            ]);

        $this->putJson("/api/admin/vendors/{$profile->id}/approve")
            ->assertForbidden();
    }
}
