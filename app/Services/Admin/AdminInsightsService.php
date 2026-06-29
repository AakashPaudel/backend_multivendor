<?php

namespace App\Services\Admin;

use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Enums\VendorApprovalStatus;
use App\Models\Order;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\VendorProfile;
use App\Services\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminInsightsService extends Service
{
    public function dashboard(): array
    {
        return Cache::remember('admin:dashboard', now()->addMinutes(5), function (): array {
            return [
                'metrics' => [
                    'total_users' => User::query()->count(),
                    'total_vendors' => User::query()->where('role', UserRole::Vendor)->count(),
                    'total_customers' => User::query()->where('role', UserRole::Customer)->count(),
                    'approved_vendors' => VendorProfile::query()->where('approval_status', VendorApprovalStatus::Approved)->count(),
                    'pending_vendors' => VendorProfile::query()->where('approval_status', VendorApprovalStatus::Pending)->count(),
                    'total_orders' => Order::query()->count(),
                    'paid_orders' => Order::query()->where('payment_status', PaymentStatus::Paid)->count(),
                    'failed_orders' => Order::query()->where('payment_status', PaymentStatus::Failed)->count(),
                    'cancelled_orders' => Order::query()->where('payment_status', PaymentStatus::Cancelled)->count(),
                    'gross_revenue' => $this->money(
                        Order::query()->where('payment_status', PaymentStatus::Paid)->sum('grand_total')
                    ),
                    'commission_earned' => $this->money(
                        DB::table('order_items')
                            ->join('orders', 'orders.id', '=', 'order_items.order_id')
                            ->where('orders.payment_status', PaymentStatus::Paid->value)
                            ->sum('order_items.commission_amount')
                    ),
                    'failed_jobs' => DB::table('failed_jobs')->count(),
                ],
                'recent_orders' => Order::query()
                    ->with(['user', 'payments' => fn ($query) => $query->latest('id')])
                    ->latest('id')
                    ->limit(5)
                    ->get()
                    ->map(fn (Order $order): array => [
                        'order_number' => $order->order_number,
                        'customer_name' => $order->user?->name,
                        'grand_total' => $order->grand_total,
                        'payment_status' => $order->payment_status->value,
                        'order_status' => $order->order_status->value,
                        'placed_at' => $order->placed_at?->toISOString(),
                    ])
                    ->all(),
                'top_vendors' => DB::table('vendor_orders')
                    ->join('orders', 'orders.id', '=', 'vendor_orders.order_id')
                    ->join('users', 'users.id', '=', 'vendor_orders.vendor_id')
                    ->leftJoin('vendor_profiles', 'vendor_profiles.user_id', '=', 'users.id')
                    ->where('orders.payment_status', PaymentStatus::Paid->value)
                    ->selectRaw('users.id as vendor_id, COALESCE(vendor_profiles.store_name, users.name) as vendor_name, COUNT(vendor_orders.id) as orders_count, SUM(vendor_orders.net_amount) as net_sales')
                    ->groupBy('users.id', 'vendor_profiles.store_name', 'users.name')
                    ->orderByDesc('net_sales')
                    ->limit(5)
                    ->get()
                    ->map(fn ($row): array => [
                        'vendor_id' => (int) $row->vendor_id,
                        'vendor_name' => $row->vendor_name,
                        'orders_count' => (int) $row->orders_count,
                        'net_sales' => $this->money($row->net_sales),
                    ])
                    ->all(),
            ];
        });
    }

    public function reports(array $filters): array
    {
        $ordersQuery = Order::query();

        if (! empty($filters['date_from'])) {
            $ordersQuery->whereDate('placed_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $ordersQuery->whereDate('placed_at', '<=', $filters['date_to']);
        }

        $paidOrders = (clone $ordersQuery)->where('payment_status', PaymentStatus::Paid);

        return [
            'filters' => [
                'date_from' => $filters['date_from'] ?? null,
                'date_to' => $filters['date_to'] ?? null,
            ],
            'summary' => [
                'orders_count' => (clone $ordersQuery)->count(),
                'paid_orders_count' => (clone $paidOrders)->count(),
                'gross_revenue' => $this->money((clone $paidOrders)->sum('grand_total')),
                'commission_earned' => $this->money(
                    DB::table('order_items')
                        ->join('orders', 'orders.id', '=', 'order_items.order_id')
                        ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('orders.placed_at', '>=', $date))
                        ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('orders.placed_at', '<=', $date))
                        ->where('orders.payment_status', PaymentStatus::Paid->value)
                        ->sum('order_items.commission_amount')
                ),
                'pending_payments' => (clone $ordersQuery)
                    ->whereIn('payment_status', [PaymentStatus::Initiated, PaymentStatus::PendingReview])
                    ->count(),
                'payment_success_count' => (clone $ordersQuery)
                    ->where('payment_status', PaymentStatus::Paid)
                    ->count(),
                'payment_failure_count' => (clone $ordersQuery)
                    ->whereIn('payment_status', [PaymentStatus::Failed, PaymentStatus::Cancelled])
                    ->count(),
            ],
            'top_products' => DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('orders.placed_at', '>=', $date))
                ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('orders.placed_at', '<=', $date))
                ->where('orders.payment_status', PaymentStatus::Paid->value)
                ->selectRaw('product_name_snapshot as product_name, sku_snapshot as sku, SUM(quantity) as quantity_sold, SUM(line_total) as gross_sales')
                ->groupBy('product_name_snapshot', 'sku_snapshot')
                ->orderByDesc('quantity_sold')
                ->limit(5)
                ->get()
                ->map(fn ($row): array => [
                    'product_name' => $row->product_name,
                    'sku' => $row->sku,
                    'quantity_sold' => (int) $row->quantity_sold,
                    'gross_sales' => $this->money($row->gross_sales),
                ])
                ->all(),
            'top_vendors' => DB::table('vendor_orders')
                ->join('orders', 'orders.id', '=', 'vendor_orders.order_id')
                ->join('users', 'users.id', '=', 'vendor_orders.vendor_id')
                ->leftJoin('vendor_profiles', 'vendor_profiles.user_id', '=', 'users.id')
                ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('orders.placed_at', '>=', $date))
                ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('orders.placed_at', '<=', $date))
                ->where('orders.payment_status', PaymentStatus::Paid->value)
                ->selectRaw('users.id as vendor_id, COALESCE(vendor_profiles.store_name, users.name) as vendor_name, COUNT(vendor_orders.id) as orders_count, SUM(vendor_orders.net_amount) as net_sales')
                ->groupBy('users.id', 'vendor_profiles.store_name', 'users.name')
                ->orderByDesc('net_sales')
                ->limit(5)
                ->get()
                ->map(fn ($row): array => [
                    'vendor_id' => (int) $row->vendor_id,
                    'vendor_name' => $row->vendor_name,
                    'orders_count' => (int) $row->orders_count,
                    'net_sales' => $this->money($row->net_sales),
                ])
                ->all(),
        ];
    }

    public function users(array $filters): LengthAwarePaginator
    {
        return User::query()
            ->with(['customerProfile.defaultAddress', 'vendorProfile'])
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->where('role', $role))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('id')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function settings(): array
    {
        return Cache::remember('admin:settings', now()->addMinutes(10), function (): array {
            return PlatformSetting::query()
                ->orderBy('key')
                ->get()
                ->map(fn (PlatformSetting $setting): array => [
                    'key' => $setting->key,
                    'value' => $setting->value,
                    'updated_at' => $setting->updated_at?->toISOString(),
                ])
                ->all();
        });
    }

    private function money(float|int|string|null $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }
}
