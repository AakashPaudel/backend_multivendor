<?php

namespace Tests\Feature\Vendor;

use App\Enums\ProductStatus;
use App\Enums\VendorApprovalStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VendorProductManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_pending_rejected_and_suspended_vendors_cannot_activate_products(): void
    {
        $statuses = [
            VendorApprovalStatus::Pending,
            VendorApprovalStatus::Rejected,
            VendorApprovalStatus::Suspended,
        ];

        $category = Category::factory()->create();

        foreach ($statuses as $status) {
            $vendor = User::factory()->vendor()->create();
            VendorProfile::factory()->for($vendor)->create([
                'approval_status' => $status,
            ]);

            Sanctum::actingAs($vendor);

            $this->postJson('/api/vendor/products', [
                'category_id' => $category->id,
                'name' => 'Restricted Product '.$status->value,
                'price' => 100,
                'stock_quantity' => 10,
                'status' => ProductStatus::Active->value,
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('status');
        }
    }

    public function test_approved_vendor_can_manage_only_own_products_and_media_paths_are_stored_on_public_disk(): void
    {
        Storage::fake('public');

        $approvedVendor = User::factory()->vendor()->create();
        VendorProfile::factory()->for($approvedVendor)->create([
            'approval_status' => VendorApprovalStatus::Approved,
        ]);

        $otherVendor = User::factory()->vendor()->create();
        VendorProfile::factory()->for($otherVendor)->create([
            'approval_status' => VendorApprovalStatus::Approved,
        ]);

        $category = Category::factory()->create();

        Sanctum::actingAs($approvedVendor);

        $response = $this->withHeader('Accept', 'application/json')->post('/api/vendor/products', [
            'category_id' => $category->id,
            'name' => 'Laptop Pro',
            'price' => 1500,
            'stock_quantity' => 20,
            'status' => ProductStatus::Active->value,
            'thumbnail' => UploadedFile::fake()->image('thumb.jpg'),
            'images' => [
                UploadedFile::fake()->image('gallery-1.jpg'),
                UploadedFile::fake()->image('gallery-2.jpg'),
            ],
        ]);

        $productId = $response->json('id');

        $response
            ->assertOk()
            ->assertJsonPath('slug', 'laptop-pro')
            ->assertJsonPath('sku', 'LAPTOP-0001')
            ->assertJsonPath('status', ProductStatus::Active->value)
            ->assertJsonPath('is_sellable', true)
            ->assertJsonCount(2, 'images');

        $product = Product::with('images')->findOrFail($productId);
        Storage::disk('public')->assertExists($product->thumbnail_path);
        foreach ($product->images as $image) {
            Storage::disk('public')->assertExists($image->image_path);
        }

        $duplicate = $this->postJson('/api/vendor/products', [
            'category_id' => $category->id,
            'name' => 'Laptop Pro',
            'price' => 1200,
            'stock_quantity' => 15,
        ]);

        $duplicate
            ->assertOk()
            ->assertJsonPath('slug', 'laptop-pro-2')
            ->assertJsonPath('sku', 'LAPTOP-0002');

        $otherProduct = Product::factory()->for($otherVendor, 'vendor')->for($category)->create();

        $this->putJson("/api/vendor/products/{$otherProduct->id}", [
            'name' => 'Illegal Update',
        ])
            ->assertForbidden()
            ->assertJson([
                'message' => 'This action is unauthorized.',
            ]);

        $this->getJson('/api/vendor/products')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_moderate_product_status_and_audit_log_is_created(): void
    {
        $admin = User::factory()->admin()->create();
        $vendor = User::factory()->vendor()->create();
        VendorProfile::factory()->for($vendor)->create([
            'approval_status' => VendorApprovalStatus::Approved,
        ]);
        $product = Product::factory()->for($vendor, 'vendor')->create([
            'status' => ProductStatus::Draft,
        ]);

        Sanctum::actingAs($admin);

        $this->putJson("/api/admin/products/{$product->id}/status", [
            'status' => ProductStatus::Inactive->value,
        ])
            ->assertOk()
            ->assertJsonPath('status', ProductStatus::Inactive->value);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'product.status_changed',
            'entity_type' => Product::class,
            'entity_id' => $product->id,
        ]);
    }
}
