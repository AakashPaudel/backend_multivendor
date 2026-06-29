<?php

use App\Http\Controllers\Api\Admin\AdminCategoryController;
use App\Http\Controllers\Api\Admin\AdminInsightsController;
use App\Http\Controllers\Api\Admin\AdminOrderController;
use App\Http\Controllers\Api\Admin\AdminProductController;
use App\Http\Controllers\Api\Admin\AdminSettingsController;
use App\Http\Controllers\Api\Admin\AdminVendorController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\EmailVerificationController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\Customer\CustomerAddressController;
use App\Http\Controllers\Api\Customer\CustomerProfileController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RecommendationController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\Vendor\VendorInsightsController;
use App\Http\Controllers\Api\Vendor\VendorOrderController;
use App\Http\Controllers\Api\Vendor\VendorProductController;
use App\Http\Controllers\Api\Vendor\VendorProfileController;
use App\Http\Controllers\Api\VendorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('auth.')->group(function (): void {
    Route::middleware('throttle:auth')->group(function (): void {
        Route::post('register/customer', [AuthController::class, 'registerCustomer'])->name('register.customer');
        Route::post('register/vendor', [AuthController::class, 'registerVendor'])->name('register.vendor');
        Route::post('login', [AuthController::class, 'login'])->name('login');
        Route::post('forgot-password', [PasswordResetController::class, 'forgotPassword'])->name('password.email');
        Route::post('reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
    });

    Route::middleware(['auth:sanctum'])->group(function (): void {
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('email/verification-notification', [EmailVerificationController::class, 'send'])
            ->middleware('throttle:verification')
            ->name('verification.send');
    });

    Route::get('verify-email/{user}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:verification'])
        ->name('verification.verify');
});

Route::prefix('admin')->name('admin.')->middleware(['auth:sanctum', 'role:admin'])->group(function (): void {
    Route::get('dashboard', [AdminInsightsController::class, 'dashboard'])->name('dashboard');
    Route::get('reports', [AdminInsightsController::class, 'reports'])->name('reports');
    Route::get('users', [AdminInsightsController::class, 'users'])->name('users.index');

    Route::get('vendors', [AdminVendorController::class, 'index'])->name('vendors.index');
    Route::put('vendors/{vendorProfile}/approve', [AdminVendorController::class, 'approve'])->name('vendors.approve');
    Route::put('vendors/{vendorProfile}/reject', [AdminVendorController::class, 'reject'])->name('vendors.reject');
    Route::put('vendors/{vendorProfile}/suspend', [AdminVendorController::class, 'suspend'])->name('vendors.suspend');

    Route::get('categories', [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::post('categories', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::put('categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('products', [AdminProductController::class, 'index'])->name('products.index');
    Route::put('products/{product}/status', [AdminProductController::class, 'updateStatus'])->name('products.status');

    Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::put('orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');

    Route::get('settings', [AdminSettingsController::class, 'index'])->name('settings.index');
    Route::put('settings', [AdminSettingsController::class, 'update'])->name('settings.update');
    Route::get('commissions', [AdminSettingsController::class, 'commissions'])->name('commissions.index');
    Route::put('commissions', [AdminSettingsController::class, 'updateCommissions'])->name('commissions.update');
});

Route::prefix('vendor')->name('vendor.')->middleware(['auth:sanctum', 'role:vendor'])->group(function (): void {
    Route::get('dashboard', [VendorInsightsController::class, 'dashboard'])->name('dashboard');
    Route::get('profile', [VendorProfileController::class, 'show'])->name('profile.show');
    Route::put('profile', [VendorProfileController::class, 'update'])->name('profile.update');

    Route::get('products', [VendorProductController::class, 'index'])->name('products.index');
    Route::post('products', [VendorProductController::class, 'store'])->name('products.store');
    Route::get('products/{product}', [VendorProductController::class, 'show'])->name('products.show');
    Route::put('products/{product}', [VendorProductController::class, 'update'])->name('products.update');
    Route::delete('products/{product}', [VendorProductController::class, 'destroy'])->name('products.destroy');

    Route::get('orders', [VendorOrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{vendorOrder}', [VendorOrderController::class, 'show'])->name('orders.show');
    Route::put('orders/{vendorOrder}/status', [VendorOrderController::class, 'updateStatus'])->name('orders.status');
    Route::get('reports/sales', [VendorInsightsController::class, 'sales'])->name('reports.sales');
});

Route::prefix('customer')->name('customer.')->middleware(['auth:sanctum', 'role:customer'])->group(function (): void {
    Route::get('profile', [CustomerProfileController::class, 'show'])->name('profile.show');
    Route::put('profile', [CustomerProfileController::class, 'update'])->name('profile.update');

    Route::get('addresses', [CustomerAddressController::class, 'index'])->name('addresses.index');
    Route::post('addresses', [CustomerAddressController::class, 'store'])->name('addresses.store');
    Route::put('addresses/{address}', [CustomerAddressController::class, 'update'])->name('addresses.update');
    Route::delete('addresses/{address}', [CustomerAddressController::class, 'destroy'])->name('addresses.destroy');
});

Route::prefix('categories')->name('categories.')->group(function (): void {
    Route::get('/', [CategoryController::class, 'index'])->name('index');
    Route::get('{category:slug}', [CategoryController::class, 'show'])->name('show');
});

Route::prefix('products')->name('products.')->group(function (): void {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::get('{slug}', [ProductController::class, 'show'])->name('show');
});

Route::prefix('vendors')->name('vendors.')->group(function (): void {
    Route::get('/', [VendorController::class, 'index'])->name('index');
    Route::get('{slug}', [VendorController::class, 'show'])->name('show');
});

Route::prefix('search')->name('search.')->group(function (): void {
    Route::get('/', SearchController::class)->name('index');
});

Route::prefix('cart')->name('cart.')->middleware(['auth:sanctum', 'role:customer'])->group(function (): void {
    Route::get('/', [CartController::class, 'show'])->name('show');
    Route::post('items', [CartController::class, 'store'])->name('items.store');
    Route::put('items/{cartItem}', [CartController::class, 'update'])->name('items.update');
    Route::delete('items/{cartItem}', [CartController::class, 'destroy'])->name('items.destroy');
});

Route::prefix('checkout')->name('checkout.')->middleware(['auth:sanctum', 'role:customer'])->group(function (): void {
    Route::post('/', [CheckoutController::class, 'store'])->name('store');
});

Route::prefix('payments')->name('payments.')->group(function (): void {
    Route::middleware(['auth:sanctum', 'role:customer', 'throttle:payments'])->group(function (): void {
        Route::post('esewa/initiate', [PaymentController::class, 'initiateEsewa'])->name('esewa.initiate');
    });

    Route::middleware('throttle:payments')->group(function (): void {
        Route::get('esewa/success', [PaymentController::class, 'handleEsewaSuccess'])->name('esewa.success');
        Route::get('esewa/failure', [PaymentController::class, 'handleEsewaFailure'])->name('esewa.failure');
    });

    Route::middleware(['auth:sanctum', 'throttle:payments'])->group(function (): void {
        Route::post('esewa/verify', [PaymentController::class, 'verifyEsewa'])->name('esewa.verify');
    });

    Route::middleware(['auth:sanctum', 'throttle:payments'])->get('{order:order_number}/status', [PaymentController::class, 'showStatus'])
        ->name('status');
});

Route::prefix('orders')->name('orders.')->middleware('auth:sanctum')->group(function (): void {
    Route::get('/', [OrderController::class, 'index'])->name('index');
    Route::get('{order:order_number}', [OrderController::class, 'show'])->name('show');
    Route::get('{order:order_number}/invoice', [OrderController::class, 'invoice'])->name('invoice');
});

Route::prefix('recommendations')->name('recommendations.')->group(function (): void {
    Route::get('{product}', [RecommendationController::class, 'show'])->name('show');
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
})->name('sanctum.user');
