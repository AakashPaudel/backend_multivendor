<?php

namespace App\Providers;

use App\Models\Address;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Commission;
use App\Models\CustomerProfile;
use App\Models\Order;
use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Models\VendorOrder;
use App\Models\VendorProfile;
use App\Observers\CacheInvalidationObserver;
use App\Policies\AddressPolicy;
use App\Policies\CartItemPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\CustomerProfilePolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\VendorOrderPolicy;
use App\Policies\VendorProfilePolicy;
use App\Services\Support\CacheInvalidationService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());

        User::observe(CacheInvalidationObserver::class);
        VendorProfile::observe(CacheInvalidationObserver::class);
        Product::observe(CacheInvalidationObserver::class);
        ProductImage::observe(CacheInvalidationObserver::class);
        Category::observe(CacheInvalidationObserver::class);
        Order::observe(CacheInvalidationObserver::class);
        VendorOrder::observe(CacheInvalidationObserver::class);
        PlatformSetting::observe(CacheInvalidationObserver::class);
        Commission::observe(CacheInvalidationObserver::class);

        Gate::policy(Address::class, AddressPolicy::class);
        Gate::policy(CartItem::class, CartItemPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(CustomerProfile::class, CustomerProfilePolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(VendorOrder::class, VendorOrderPolicy::class);
        Gate::policy(VendorProfile::class, VendorProfilePolicy::class);

        RateLimiter::for('auth', function (Request $request): Limit {
            return Limit::perMinute(10)->by((string) ($request->user()?->id ?: $request->ip()));
        });

        RateLimiter::for('verification', function (Request $request): Limit {
            return Limit::perMinute(6)->by((string) ($request->user()?->id ?: $request->ip()));
        });

        RateLimiter::for('payments', function (Request $request): Limit {
            return Limit::perMinute(20)->by((string) ($request->user()?->id ?: $request->ip()));
        });

        Queue::failing(function (): void {
            app(CacheInvalidationService::class)->forgetAdminDashboard();
        });

        VerifyEmail::createUrlUsing(function (object $notifiable): string {
            return url()->temporarySignedRoute(
                'auth.verification.verify',
                now()->addMinutes(60),
                [
                    'user' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );
        });

        ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            return sprintf(
                '%s/reset-password?token=%s&email=%s',
                rtrim((string) config('app.frontend_url'), '/'),
                $token,
                urlencode($notifiable->getEmailForPasswordReset())
            );
        });
    }
}
