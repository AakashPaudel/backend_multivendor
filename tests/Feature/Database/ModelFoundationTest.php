<?php

namespace Tests\Feature\Database;

use App\Enums\CommissionScope;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VendorApprovalStatus;
use App\Models\Commission;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ModelFoundationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_database_seeder_creates_phase_two_defaults(): void
    {
        $this->seed();

        $this->assertDatabaseHas('users', [
            'email' => 'admin@multi-vendor.local',
            'role' => UserRole::Admin->value,
        ]);

        $this->assertDatabaseHas('commissions', [
            'scope' => CommissionScope::Global->value,
            'vendor_id' => null,
        ]);

        $this->assertTrue(PlatformSetting::query()->where('key', 'platform.name')->exists());
    }

    public function test_product_factory_builds_valid_relationships_and_casts(): void
    {
        $product = Product::factory()->create();

        $product->refresh();

        $this->assertNotNull($product->vendor);
        $this->assertNotNull($product->category);
        $this->assertInstanceOf(ProductStatus::class, $product->status);
        $this->assertInstanceOf(UserRole::class, $product->vendor->role);
    }

    public function test_recommendation_factory_builds_related_products(): void
    {
        $recommendation = Recommendation::factory()->create();

        $this->assertNotNull($recommendation->product);
        $this->assertNotNull($recommendation->recommendedProduct);
        $this->assertNotSame($recommendation->product_id, $recommendation->recommended_product_id);
    }

    public function test_commission_factory_supports_vendor_specific_scope(): void
    {
        $commission = Commission::factory()->vendorSpecific()->create();

        $this->assertSame(CommissionScope::VendorSpecific, $commission->scope);
        $this->assertNotNull($commission->vendor);
        $this->assertTrue($commission->vendor->isVendor());
    }

    public function test_model_scopes_filter_expected_marketplace_records(): void
    {
        User::factory()->admin()->inactive()->create();
        $activeVendor = User::factory()->vendor()->create();
        $pendingVendorProfile = VendorProfile::factory()->for($activeVendor)->create([
            'approval_status' => VendorApprovalStatus::Pending,
        ]);
        $approvedVendorProfile = VendorProfile::factory()->create([
            'approval_status' => VendorApprovalStatus::Approved,
        ]);

        $activeProduct = Product::factory()->create([
            'vendor_id' => $approvedVendorProfile->user_id,
            'status' => ProductStatus::Active,
            'stock_quantity' => 3,
        ]);
        Product::factory()->create([
            'status' => ProductStatus::Draft,
            'stock_quantity' => 12,
        ]);

        $pendingOrder = Order::factory()->create([
            'payment_status' => PaymentStatus::Pending,
            'order_status' => OrderStatus::PendingPayment,
        ]);
        Payment::factory()->create([
            'order_id' => $pendingOrder->id,
            'status' => PaymentStatus::Pending,
        ]);
        Payment::factory()->create([
            'status' => PaymentStatus::Paid,
        ]);

        $activeVendors = User::query()->role(UserRole::Vendor)->active()->get();

        $this->assertTrue($activeVendors->contains(fn (User $user): bool => $user->is($activeVendor)));
        $this->assertTrue(VendorProfile::query()->pending()->first()->is($pendingVendorProfile));
        $this->assertTrue(VendorProfile::query()->approved()->first()->is($approvedVendorProfile));
        $this->assertTrue(Product::query()->active()->lowStock()->first()->is($activeProduct));
        $this->assertTrue(Order::query()->pendingPayment()->status(OrderStatus::PendingPayment)->first()->is($pendingOrder));
        $this->assertCount(1, Payment::query()->pending()->get());
        $this->assertSame(UserStatus::Active, $activeVendor->fresh()->status);
    }
}
