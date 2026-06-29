<?php

namespace App\Services\Vendor;

use App\Enums\PaymentStatus;
use App\Models\User;
use App\Models\VendorOrder;
use App\Services\Service;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VendorInsightsService extends Service
{
    public function dashboard(User $vendor): array
    {
        return Cache::remember(
            'vendor:dashboard:'.$vendor->id,
            now()->addMinutes(5),
            function () use ($vendor): array {
                $vendorOrders = VendorOrder::query()->where('vendor_id', $vendor->id);

                return [
                    'metrics' => [
                        'products_count' => $vendor->products()->count(),
                        'low_stock_products_count' => $vendor->products()->where('stock_quantity', '<=', 5)->count(),
                        'vendor_orders_count' => (clone $vendorOrders)->count(),
                        'paid_vendor_orders_count' => (clone $vendorOrders)
                            ->whereHas('order', fn ($query) => $query->where('payment_status', PaymentStatus::Paid))
                            ->count(),
                        'gross_sales' => $this->money(
                            (clone $vendorOrders)
                                ->whereHas('order', fn ($query) => $query->where('payment_status', PaymentStatus::Paid))
                                ->sum('subtotal')
                        ),
                        'net_sales' => $this->money(
                            (clone $vendorOrders)
                                ->whereHas('order', fn ($query) => $query->where('payment_status', PaymentStatus::Paid))
                                ->sum('net_amount')
                        ),
                    ],
                    'best_selling_products' => DB::table('order_items')
                        ->join('orders', 'orders.id', '=', 'order_items.order_id')
                        ->where('order_items.vendor_id', $vendor->id)
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
                    'recent_vendor_orders' => VendorOrder::query()
                        ->where('vendor_id', $vendor->id)
                        ->with('order')
                        ->latest('id')
                        ->limit(5)
                        ->get()
                        ->map(fn (VendorOrder $vendorOrder): array => [
                            'id' => $vendorOrder->id,
                            'order_number' => $vendorOrder->order?->order_number,
                            'status' => $vendorOrder->status->value,
                            'subtotal' => $vendorOrder->subtotal,
                            'net_amount' => $vendorOrder->net_amount,
                        ])
                        ->all(),
                ];
            }
        );
    }

    public function salesReport(User $vendor, array $filters): array
    {
        $query = VendorOrder::query()
            ->where('vendor_id', $vendor->id)
            ->with([
                'order.user',
                'order.payments' => fn ($query) => $query->latest('id'),
                'items',
            ]);

        if (! empty($filters['date_from'])) {
            $query->whereHas('order', fn ($orderQuery) => $orderQuery->whereDate('placed_at', '>=', $filters['date_from']));
        }

        if (! empty($filters['date_to'])) {
            $query->whereHas('order', fn ($orderQuery) => $orderQuery->whereDate('placed_at', '<=', $filters['date_to']));
        }

        $paidQuery = (clone $query)->whereHas('order', fn ($orderQuery) => $orderQuery->where('payment_status', PaymentStatus::Paid));
        $paginator = $query->latest('id')->paginate((int) ($filters['per_page'] ?? 15));

        return [
            'filters' => [
                'date_from' => $filters['date_from'] ?? null,
                'date_to' => $filters['date_to'] ?? null,
            ],
            'summary' => [
                'vendor_orders_count' => $paginator->total(),
                'paid_vendor_orders_count' => (clone $paidQuery)->count(),
                'gross_sales' => $this->money((clone $paidQuery)->sum('subtotal')),
                'commission_total' => $this->money((clone $paidQuery)->sum('commission_amount')),
                'net_sales' => $this->money((clone $paidQuery)->sum('net_amount')),
            ],
            'orders' => $paginator,
        ];
    }

    private function money(float|int|string|null $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }
}
