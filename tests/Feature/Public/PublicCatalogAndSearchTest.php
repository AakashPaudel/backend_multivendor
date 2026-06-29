<?php

namespace Tests\Feature\Public;

use App\Enums\ProductStatus;
use App\Enums\UserStatus;
use App\Enums\VendorApprovalStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorOrder;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PublicCatalogAndSearchTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_public_products_and_vendors_only_return_visible_sellable_data(): void
    {
        $category = Category::factory()->create([
            'name' => 'Phones',
            'slug' => 'phones',
            'is_active' => true,
        ]);

        $approvedVendor = User::factory()->vendor()->create([
            'status' => UserStatus::Active,
        ]);
        VendorProfile::factory()->for($approvedVendor)->create([
            'store_name' => 'Approved Store',
            'slug' => 'approved-store',
            'approval_status' => VendorApprovalStatus::Approved,
        ]);

        $pendingVendor = User::factory()->vendor()->create();
        VendorProfile::factory()->for($pendingVendor)->create([
            'store_name' => 'Pending Store',
            'slug' => 'pending-store',
            'approval_status' => VendorApprovalStatus::Pending,
        ]);

        $inactiveApprovedVendor = User::factory()->vendor()->inactive()->create();
        VendorProfile::factory()->for($inactiveApprovedVendor)->create([
            'store_name' => 'Inactive Store',
            'slug' => 'inactive-store',
            'approval_status' => VendorApprovalStatus::Approved,
        ]);

        $visibleProduct = Product::factory()->for($approvedVendor, 'vendor')->for($category)->create([
            'name' => 'Visible Phone',
            'slug' => 'visible-phone',
            'status' => ProductStatus::Active,
            'stock_quantity' => 10,
        ]);

        Product::factory()->for($approvedVendor, 'vendor')->for($category)->create([
            'name' => 'Out Of Stock Phone',
            'slug' => 'out-of-stock-phone',
            'status' => ProductStatus::Active,
            'stock_quantity' => 0,
        ]);

        Product::factory()->for($pendingVendor, 'vendor')->for($category)->create([
            'name' => 'Pending Vendor Phone',
            'slug' => 'pending-vendor-phone',
            'status' => ProductStatus::Active,
            'stock_quantity' => 5,
        ]);

        Product::factory()->for($inactiveApprovedVendor, 'vendor')->for($category)->create([
            'name' => 'Inactive Vendor Phone',
            'slug' => 'inactive-vendor-phone',
            'status' => ProductStatus::Active,
            'stock_quantity' => 5,
        ]);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'visible-phone')
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/products/visible-phone')
            ->assertOk()
            ->assertJsonPath('slug', $visibleProduct->slug)
            ->assertJsonPath('is_sellable', true);

        $this->getJson('/api/products/pending-vendor-phone')
            ->assertNotFound();

        $this->getJson('/api/vendors')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'approved-store')
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/vendors/approved-store')
            ->assertOk()
            ->assertJsonPath('slug', 'approved-store');

        $this->getJson('/api/vendors/pending-store')
            ->assertNotFound();
    }

    public function test_categories_endpoint_can_be_called_repeatedly_without_cached_paginator_failures(): void
    {
        Category::factory()->create([
            'name' => 'Phones',
            'slug' => 'phones',
            'is_active' => true,
            'parent_id' => null,
        ]);

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'phones');

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'phones');
    }

    public function test_search_filters_sorts_and_pagination_work_for_public_catalog(): void
    {
        $phones = Category::factory()->create([
            'name' => 'Phones',
            'slug' => 'phones',
            'is_active' => true,
        ]);
        $laptops = Category::factory()->create([
            'name' => 'Laptops',
            'slug' => 'laptops',
            'is_active' => true,
        ]);

        $vendorA = User::factory()->vendor()->create();
        VendorProfile::factory()->for($vendorA)->create([
            'store_name' => 'alpha hub',
            'slug' => 'alpha-hub',
            'approval_status' => VendorApprovalStatus::Approved,
        ]);

        $vendorB = User::factory()->vendor()->create();
        VendorProfile::factory()->for($vendorB)->create([
            'store_name' => 'beta mart',
            'slug' => 'beta-mart',
            'approval_status' => VendorApprovalStatus::Approved,
        ]);

        $alphaPhone = Product::factory()->for($vendorA, 'vendor')->for($phones)->create([
            'name' => 'Alpha Phone',
            'slug' => 'alpha-phone',
            'price' => 100,
            'status' => ProductStatus::Active,
            'stock_quantity' => 10,
        ]);
        $betaPhone = Product::factory()->for($vendorA, 'vendor')->for($phones)->create([
            'name' => 'Beta Phone',
            'slug' => 'beta-phone',
            'price' => 250,
            'status' => ProductStatus::Active,
            'stock_quantity' => 10,
        ]);
        $gammaLaptop = Product::factory()->for($vendorB, 'vendor')->for($laptops)->create([
            'name' => 'Gamma Laptop',
            'slug' => 'gamma-laptop',
            'price' => 400,
            'status' => ProductStatus::Active,
            'stock_quantity' => 10,
        ]);

        $order = Order::factory()->create();
        $vendorOrder = VendorOrder::factory()->create([
            'order_id' => $order->id,
            'vendor_id' => $vendorA->id,
        ]);
        OrderItem::factory()->count(2)->create([
            'order_id' => $order->id,
            'vendor_order_id' => $vendorOrder->id,
            'product_id' => $alphaPhone->id,
            'vendor_id' => $vendorA->id,
            'product_name_snapshot' => $alphaPhone->name,
            'sku_snapshot' => $alphaPhone->sku,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'vendor_order_id' => $vendorOrder->id,
            'product_id' => $betaPhone->id,
            'vendor_id' => $vendorA->id,
            'product_name_snapshot' => $betaPhone->name,
            'sku_snapshot' => $betaPhone->sku,
        ]);

        $this->getJson('/api/search?q=phone')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);

        $this->getJson('/api/search?category=phones')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);

        $this->getJson('/api/search?vendor=beta-mart')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'gamma-laptop');

        $this->getJson('/api/search?min_price=150&max_price=300')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'beta-phone');

        $this->getJson('/api/products?sort=price_asc')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'alpha-phone')
            ->assertJsonPath('data.1.slug', 'beta-phone')
            ->assertJsonPath('data.2.slug', 'gamma-laptop');

        $this->getJson('/api/products?sort=price_desc')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'gamma-laptop');

        $this->getJson('/api/products?sort=popularity')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'alpha-phone');

        $this->getJson('/api/products?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);
    }
}
