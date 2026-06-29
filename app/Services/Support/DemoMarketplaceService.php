<?php

namespace App\Services\Support;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentVerificationStatus;
use App\Enums\ProductStatus;
use App\Enums\VendorApprovalStatus;
use App\Enums\VendorOrderStatus;
use App\Models\Address;
use App\Models\Category;
use App\Models\CustomerProfile;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Models\VendorOrder;
use App\Models\VendorProfile;
use App\Services\Recommendation\RecommendationService;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\CommissionSeeder;
use Database\Seeders\PlatformSettingsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoMarketplaceService
{
    /**
     * @param  array<string, mixed>  $options
     * @return array<string, int|bool>
     */
    public function populate(array $options, ?Command $command = null): array
    {
        $categoryCount = max((int) ($options['categories'] ?? 12), 4);
        $vendorCount = max((int) ($options['vendors'] ?? 12), 3);
        $customerCount = max((int) ($options['customers'] ?? 30), 5);
        $orderCount = max((int) ($options['orders'] ?? 120), 10);
        $productsMin = max((int) ($options['products_min'] ?? 6), 2);
        $productsMax = max((int) ($options['products_max'] ?? 12), $productsMin);
        $shouldReset = (bool) ($options['reset'] ?? false);

        $productImageNumber = 1;
        $vendorImageNumber = 21;

        $nextProductImagePath = static function (string $directory) use (&$productImageNumber): string {
            $path = sprintf('%s/%d.jpg', trim($directory, '/'), $productImageNumber);
            $productImageNumber = $productImageNumber >= 20 ? 1 : $productImageNumber + 1;

            return $path;
        };

        $nextVendorImagePath = static function (string $directory) use (&$vendorImageNumber): string {
            $path = sprintf('%s/%d.jpg', trim($directory, '/'), $vendorImageNumber);
            $vendorImageNumber = $vendorImageNumber >= 30 ? 21 : $vendorImageNumber + 1;

            return $path;
        };

        if ($shouldReset) {
            $this->resetMarketplaceTables();
        }

        app(AdminUserSeeder::class)->run();
        app(PlatformSettingsSeeder::class)->run();
        app(CommissionSeeder::class)->run();

        $command?->info('Creating categories...');

        $parentCategories = Category::factory()
            ->count((int) ceil($categoryCount / 3))
            ->create();

        $childCategories = Category::factory()
            ->count($categoryCount - $parentCategories->count())
            ->create([
                'parent_id' => fn () => $parentCategories->random()->id,
            ]);

        $categories = $parentCategories->concat($childCategories);

        $approvedVendorCount = max($vendorCount - 2, 1);
        $pendingVendorCount = min(1, max($vendorCount - $approvedVendorCount, 0));
        $suspendedVendorCount = max($vendorCount - $approvedVendorCount - $pendingVendorCount, 0);

        $command?->info('Creating vendors and products...');

        $approvedVendors = collect();
        $pendingVendors = collect();
        $suspendedVendors = collect();
        $adminId = User::query()->where('email', 'admin@multi-vendor.local')->value('id');

        foreach (range(1, $approvedVendorCount) as $index) {
            $vendor = User::factory()->vendor()->create();
            VendorProfile::factory()->for($vendor)->create([
                'approval_status' => VendorApprovalStatus::Approved,
                'approved_at' => now()->subDays(random_int(5, 90)),
                'approved_by' => $adminId,
                'commission_rate_override' => $index % 3 === 0 ? fake()->randomFloat(2, 5, 12) : null,
                'logo_path' => $nextVendorImagePath('vendors/logos'),
                'banner_path' => $nextVendorImagePath('vendors/banners'),
            ]);

            $approvedVendors->push($vendor);

            $productCount = random_int($productsMin, $productsMax);

            foreach (range(1, $productCount) as $productIndex) {
                $status = $productIndex <= 2
                    ? ProductStatus::Active
                    : fake()->randomElement([
                        ProductStatus::Active,
                        ProductStatus::Active,
                        ProductStatus::Active,
                        ProductStatus::Draft,
                        ProductStatus::Inactive,
                    ]);

                $product = Product::factory()
                    ->for($vendor, 'vendor')
                    ->for($categories->random())
                    ->create([
                        'status' => $status,
                        'stock_quantity' => $status === ProductStatus::Active ? random_int(5, 80) : random_int(0, 20),
                        'price' => fake()->randomFloat(2, 100, 25000),
                        'discount_price' => $status === ProductStatus::Active && $productIndex % 2 === 0
                            ? fake()->randomFloat(2, 50, 20000)
                            : null,
                        'thumbnail_path' => $nextProductImagePath('products/thumbnails'),
                    ]);

                foreach (range(0, random_int(1, 3) - 1) as $galleryIndex) {
                    ProductImage::query()->create([
                        'product_id' => $product->id,
                        'image_path' => $nextProductImagePath('products/gallery'),
                        'sort_order' => $galleryIndex,
                    ]);
                }
            }
        }

        foreach (range(1, $pendingVendorCount) as $unused) {
            $vendor = User::factory()->vendor()->create();
            VendorProfile::factory()->for($vendor)->create([
                'approval_status' => VendorApprovalStatus::Pending,
                'logo_path' => $nextVendorImagePath('vendors/logos'),
                'banner_path' => $nextVendorImagePath('vendors/banners'),
            ]);
            $pendingVendors->push($vendor);
        }

        foreach (range(1, $suspendedVendorCount) as $unused) {
            $vendor = User::factory()->vendor()->suspended()->create();
            VendorProfile::factory()->for($vendor)->create([
                'approval_status' => VendorApprovalStatus::Suspended,
                'rejection_reason' => 'Demo suspension state',
                'logo_path' => $nextVendorImagePath('vendors/logos'),
                'banner_path' => $nextVendorImagePath('vendors/banners'),
            ]);
            $suspendedVendors->push($vendor);
        }

        $command?->info('Creating customers and addresses...');

        $customers = collect();

        foreach (range(1, $customerCount) as $unused) {
            $customer = User::factory()->customer()->create();
            $profile = CustomerProfile::factory()->for($customer)->create();

            $addresses = Address::factory()
                ->count(random_int(1, 3))
                ->for($customer)
                ->create();

            $defaultAddress = $addresses->first();
            $defaultAddress?->update(['is_default' => true]);
            $profile->update(['default_address_id' => $defaultAddress?->id]);

            $customers->push($customer->fresh('customerProfile.defaultAddress'));
        }

        $sellableProducts = Product::query()
            ->visible()
            ->with(['vendor.vendorProfile', 'category'])
            ->get();

        if ($sellableProducts->count() < 3) {
            $command?->error('Not enough sellable products were created to generate realistic orders.');

            return ['success' => false];
        }

        $command?->info('Creating orders, vendor orders, items, payments, and status histories...');

        $orderStats = [
            PaymentStatus::Paid->value => 0,
            PaymentStatus::Initiated->value => 0,
            PaymentStatus::Failed->value => 0,
            PaymentStatus::Cancelled->value => 0,
            PaymentStatus::PendingReview->value => 0,
        ];

        foreach (range(1, $orderCount) as $orderIndex) {
            $customer = $customers->random();
            $address = Address::query()
                ->where('user_id', $customer->id)
                ->inRandomOrder()
                ->firstOrFail();

            $selectionMinimum = $orderIndex <= 3 ? 2 : 1;
            $selectionMaximum = min(4, $sellableProducts->count());

            $selectedProducts = $sellableProducts
                ->random(random_int($selectionMinimum, $selectionMaximum))
                ->groupBy('vendor_id');

            $lineItems = collect();
            $subtotal = 0.0;
            $discountTotal = 0.0;

            foreach ($selectedProducts as $vendorId => $vendorProducts) {
                foreach ($vendorProducts as $product) {
                    $quantity = random_int(1, min(3, max((int) $product->stock_quantity, 1)));
                    $basePrice = (float) $product->price;
                    $currentPrice = $product->currentUnitPrice();
                    $lineSubtotal = round($basePrice * $quantity, 2);
                    $lineTotal = round($currentPrice * $quantity, 2);
                    $commissionAmount = round($lineTotal * 0.10, 2);

                    $lineItems->push([
                        'vendor_id' => (int) $vendorId,
                        'product' => $product,
                        'quantity' => $quantity,
                        'unit_price' => $currentPrice,
                        'line_subtotal' => $lineSubtotal,
                        'line_total' => $lineTotal,
                        'discount_total' => round($lineSubtotal - $lineTotal, 2),
                        'commission_amount' => $commissionAmount,
                        'net_amount' => round($lineTotal - $commissionAmount, 2),
                    ]);

                    $subtotal += $lineSubtotal;
                    $discountTotal += round($lineSubtotal - $lineTotal, 2);
                }
            }

            $shippingTotal = (float) fake()->randomElement([0, 75, 100, 150, 200]);
            $taxTotal = round(($subtotal - $discountTotal) * 0.13, 2);
            $grandTotal = round($subtotal - $discountTotal + $shippingTotal + $taxTotal, 2);

            $paymentStatus = $orderIndex <= 3
                ? PaymentStatus::Paid
                : fake()->randomElement([
                    PaymentStatus::Paid,
                    PaymentStatus::Paid,
                    PaymentStatus::Paid,
                    PaymentStatus::Initiated,
                    PaymentStatus::Failed,
                    PaymentStatus::Cancelled,
                    PaymentStatus::PendingReview,
                ]);

            $orderStatus = match ($paymentStatus) {
                PaymentStatus::Paid => fake()->randomElement([
                    OrderStatus::Paid,
                    OrderStatus::Processing,
                    OrderStatus::PartiallyShipped,
                    OrderStatus::Completed,
                ]),
                PaymentStatus::Initiated, PaymentStatus::PendingReview => OrderStatus::PaymentInitiated,
                PaymentStatus::Cancelled => OrderStatus::Cancelled,
                PaymentStatus::Failed => OrderStatus::Failed,
                default => OrderStatus::PendingPayment,
            };

            $verificationStatus = match ($paymentStatus) {
                PaymentStatus::Paid => PaymentVerificationStatus::Verified,
                PaymentStatus::PendingReview => PaymentVerificationStatus::Pending,
                PaymentStatus::Cancelled, PaymentStatus::Failed => PaymentVerificationStatus::Failed,
                default => PaymentVerificationStatus::Pending,
            };

            $placedAt = now()->subDays(random_int(0, 90))->subMinutes(random_int(0, 1440));

            $order = Order::factory()->create([
                'user_id' => $customer->id,
                'address_id' => $address->id,
                'order_number' => sprintf('ORD-DEMO-%s-%04d-%s', now()->format('Ymd'), $orderIndex, Str::upper(Str::random(4))),
                'subtotal' => number_format($subtotal, 2, '.', ''),
                'discount_total' => number_format($discountTotal, 2, '.', ''),
                'shipping_total' => number_format($shippingTotal, 2, '.', ''),
                'tax_total' => number_format($taxTotal, 2, '.', ''),
                'grand_total' => number_format($grandTotal, 2, '.', ''),
                'payment_status' => $paymentStatus,
                'order_status' => $orderStatus,
                'notes' => fake()->optional()->sentence(),
                'placed_at' => $placedAt,
            ]);

            foreach ($lineItems->groupBy('vendor_id') as $vendorId => $vendorLines) {
                $vendorOrderStatus = match ($orderStatus) {
                    OrderStatus::Completed => VendorOrderStatus::Delivered,
                    OrderStatus::PartiallyShipped => fake()->randomElement([VendorOrderStatus::Shipped, VendorOrderStatus::OutForDelivery, VendorOrderStatus::Delivered]),
                    OrderStatus::Processing => fake()->randomElement([VendorOrderStatus::Accepted, VendorOrderStatus::Packed]),
                    OrderStatus::Cancelled => VendorOrderStatus::Cancelled,
                    OrderStatus::Failed, OrderStatus::PaymentInitiated => VendorOrderStatus::New,
                    default => VendorOrderStatus::New,
                };

                $vendorOrder = VendorOrder::factory()->create([
                    'order_id' => $order->id,
                    'vendor_id' => (int) $vendorId,
                    'subtotal' => number_format($vendorLines->sum('line_total'), 2, '.', ''),
                    'commission_amount' => number_format($vendorLines->sum('commission_amount'), 2, '.', ''),
                    'net_amount' => number_format($vendorLines->sum('net_amount'), 2, '.', ''),
                    'status' => $vendorOrderStatus,
                ]);

                foreach ($vendorLines as $line) {
                    OrderItem::factory()->create([
                        'order_id' => $order->id,
                        'vendor_order_id' => $vendorOrder->id,
                        'product_id' => $line['product']->id,
                        'vendor_id' => $vendorOrder->vendor_id,
                        'product_name_snapshot' => $line['product']->name,
                        'sku_snapshot' => $line['product']->sku,
                        'unit_price' => number_format($line['unit_price'], 2, '.', ''),
                        'quantity' => $line['quantity'],
                        'line_total' => number_format($line['line_total'], 2, '.', ''),
                        'commission_amount' => number_format($line['commission_amount'], 2, '.', ''),
                        'net_amount' => number_format($line['net_amount'], 2, '.', ''),
                        'status' => $vendorOrderStatus->value,
                    ]);
                }

                $order->statusHistories()->create([
                    'vendor_order_id' => $vendorOrder->id,
                    'status' => $vendorOrderStatus->value,
                    'message' => sprintf('Demo vendor order created for vendor %d.', $vendorOrder->vendor_id),
                    'changed_by' => $customer->id,
                    'created_at' => $placedAt,
                ]);
            }

            Payment::factory()->create([
                'order_id' => $order->id,
                'amount' => number_format($grandTotal, 2, '.', ''),
                'transaction_uuid' => (string) Str::uuid(),
                'gateway_reference' => $paymentStatus === PaymentStatus::Paid ? 'DEMO-REF-'.Str::upper(Str::random(8)) : null,
                'status' => $paymentStatus,
                'verification_status' => $verificationStatus,
                'raw_request_json' => [
                    'provider' => 'esewa',
                    'mode' => 'demo',
                    'order_number' => $order->order_number,
                ],
                'raw_response_json' => [
                    'provider' => 'esewa',
                    'mode' => 'demo',
                    'status' => $paymentStatus->value,
                ],
                'paid_at' => $paymentStatus === PaymentStatus::Paid ? $placedAt->copy()->addMinutes(random_int(2, 45)) : null,
            ]);

            $order->statusHistories()->create([
                'status' => $orderStatus->value,
                'message' => sprintf('Demo order created with payment status %s.', $paymentStatus->value),
                'changed_by' => $customer->id,
                'created_at' => $placedAt,
            ]);

            $orderStats[$paymentStatus->value]++;
        }

        app(RecommendationService::class)->generate(8);

        return [
            'success' => true,
            'categories' => Category::query()->count(),
            'approved_vendors' => $approvedVendors->count(),
            'pending_vendors' => $pendingVendors->count(),
            'suspended_vendors' => $suspendedVendors->count(),
            'customers' => $customers->count(),
            'products' => Product::query()->count(),
            'orders' => Order::query()->count(),
            'paid_payments' => $orderStats[PaymentStatus::Paid->value],
            'initiated_payments' => $orderStats[PaymentStatus::Initiated->value],
            'pending_review_payments' => $orderStats[PaymentStatus::PendingReview->value],
            'failed_payments' => $orderStats[PaymentStatus::Failed->value],
            'cancelled_payments' => $orderStats[PaymentStatus::Cancelled->value],
        ];
    }

    private function resetMarketplaceTables(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ([
            'recommendations',
            'order_status_histories',
            'payments',
            'order_items',
            'vendor_orders',
            'orders',
            'cart_items',
            'carts',
            'product_images',
            'products',
            'categories',
            'addresses',
            'customer_profiles',
            'vendor_profiles',
            'users',
        ] as $table) {
            DB::table($table)->truncate();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
