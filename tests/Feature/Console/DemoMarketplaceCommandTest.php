<?php

namespace Tests\Feature\Console;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\VendorOrder;
use App\Models\VendorProfile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DemoMarketplaceCommandTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_demo_marketplace_command_creates_live_like_platform_data(): void
    {
        $exitCode = Artisan::call('demo:populate-marketplace', [
            '--categories' => 6,
            '--vendors' => 4,
            '--customers' => 6,
            '--orders' => 12,
            '--products-min' => 2,
            '--products-max' => 3,
            '--reset' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame(6, Category::query()->count());
        $this->assertSame(4, VendorProfile::query()->count());
        $this->assertSame(6, User::query()->where('role', 'customer')->count());
        $this->assertGreaterThanOrEqual(4, Product::query()->count());
        $this->assertSame(12, Order::query()->count());
        $this->assertSame(12, Payment::query()->count());
        $this->assertGreaterThan(0, VendorOrder::query()->count());
        $this->assertGreaterThan(0, OrderItem::query()->count());
        $this->assertGreaterThan(0, ProductImage::query()->count());
        $this->assertGreaterThan(0, Recommendation::query()->count());

        $this->assertDatabaseHas('users', [
            'email' => 'admin@multi-vendor.local',
        ]);

        $this->assertMatchesRegularExpression(
            '#^vendors/logos/(2[1-9]|30)\.jpg$#',
            (string) VendorProfile::query()->whereNotNull('logo_path')->value('logo_path')
        );

        $this->assertMatchesRegularExpression(
            '#^products/thumbnails/([1-9]|1[0-9]|20)\.jpg$#',
            (string) Product::query()->whereNotNull('thumbnail_path')->value('thumbnail_path')
        );

        $this->assertMatchesRegularExpression(
            '#^products/gallery/([1-9]|1[0-9]|20)\.jpg$#',
            (string) ProductImage::query()->whereNotNull('image_path')->value('image_path')
        );
    }
}
