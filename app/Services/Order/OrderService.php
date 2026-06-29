<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\VendorOrderStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Models\VendorOrder;
use App\Services\Service;
use App\Services\Support\AuditLogService;
use App\Services\Support\MarketplaceNotificationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService extends Service
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly MarketplaceNotificationService $marketplaceNotificationService,
    ) {}

    public function paginateForCustomer(User $customer): LengthAwarePaginator
    {
        return Order::query()
            ->where('user_id', $customer->id)
            ->withCount(['items', 'vendorOrders'])
            ->with($this->orderListRelations())
            ->latest('id')
            ->paginate();
    }

    public function detailForCustomer(Order $order): Order
    {
        return $order->load($this->orderDetailRelations());
    }

    public function paginateForAdmin(): LengthAwarePaginator
    {
        return Order::query()
            ->withCount(['items', 'vendorOrders'])
            ->with($this->orderListRelations())
            ->latest('id')
            ->paginate();
    }

    public function detailForAdmin(Order $order): Order
    {
        return $order->load($this->orderDetailRelations());
    }

    public function paginateVendorOrders(User $vendor): LengthAwarePaginator
    {
        return VendorOrder::query()
            ->where('vendor_id', $vendor->id)
            ->with($this->vendorOrderRelations())
            ->latest('id')
            ->paginate();
    }

    public function detailVendorOrder(VendorOrder $vendorOrder): VendorOrder
    {
        return $vendorOrder->load($this->vendorOrderRelations());
    }

    public function updateVendorOrderStatus(User $actor, VendorOrder $vendorOrder, array $attributes): VendorOrder
    {
        return DB::transaction(function () use ($actor, $attributes, $vendorOrder): VendorOrder {
            $vendorOrder = VendorOrder::query()
                ->with(['order', 'vendor'])
                ->lockForUpdate()
                ->findOrFail($vendorOrder->id);

            $targetStatus = $attributes['status'] instanceof VendorOrderStatus
                ? $attributes['status']
                : VendorOrderStatus::from($attributes['status']);

            $order = Order::query()->lockForUpdate()->findOrFail($vendorOrder->order_id);

            if ($order->payment_status !== PaymentStatus::Paid) {
                throw ValidationException::withMessages([
                    'status' => ['Vendor orders can only be processed after payment is verified.'],
                ]);
            }

            $this->ensureAllowedVendorTransition($vendorOrder->status, $targetStatus);

            $previousStatus = $vendorOrder->status;

            if ($previousStatus !== $targetStatus) {
                $vendorOrder->update([
                    'status' => $targetStatus,
                ]);

                $this->recordStatusHistory(
                    $order,
                    $targetStatus->value,
                    $attributes['message'] ?? sprintf('Vendor order moved from %s to %s.', $previousStatus->value, $targetStatus->value),
                    $actor->id,
                    $vendorOrder
                );

                $this->auditLogService->record($actor, 'vendor_order.status_updated', $vendorOrder, [
                    'order_number' => $order->order_number,
                    'previous_status' => $previousStatus->value,
                    'new_status' => $targetStatus->value,
                ]);

                $this->syncTopLevelStatusFromVendorOrders($order, $actor);
            }

            return $vendorOrder->fresh($this->vendorOrderRelations());
        });
    }

    public function updateOrderStatus(User $actor, Order $order, array $attributes): Order
    {
        return DB::transaction(function () use ($actor, $attributes, $order): Order {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            $targetStatus = $attributes['status'] instanceof OrderStatus
                ? $attributes['status']
                : OrderStatus::from($attributes['status']);

            $previousStatus = $order->order_status;

            if ($previousStatus !== $targetStatus) {
                $order->update([
                    'order_status' => $targetStatus,
                ]);

                $this->recordStatusHistory(
                    $order,
                    $targetStatus->value,
                    $attributes['message'] ?? sprintf('Admin changed order status from %s to %s.', $previousStatus->value, $targetStatus->value),
                    $actor->id
                );

                $this->auditLogService->record($actor, 'order.admin_status_updated', $order, [
                    'previous_status' => $previousStatus->value,
                    'new_status' => $targetStatus->value,
                ]);

                $this->marketplaceNotificationService->sendOrderStatusChanged(
                    $order->fresh($this->orderDetailRelations()),
                    sprintf('Order status changed from %s to %s.', $previousStatus->value, $targetStatus->value)
                );
            }

            return $order->fresh($this->orderDetailRelations());
        });
    }

    public function syncTopLevelStatusFromVendorOrders(Order $order, ?User $actor = null): Order
    {
        $order->loadMissing('vendorOrders');

        $statuses = $order->vendorOrders->pluck('status');

        if ($statuses->isEmpty()) {
            return $order;
        }

        $nextStatus = match (true) {
            $statuses->every(fn (VendorOrderStatus $status): bool => $status === VendorOrderStatus::Cancelled) => OrderStatus::Cancelled,
            $statuses->every(fn (VendorOrderStatus $status): bool => $status === VendorOrderStatus::Delivered) => OrderStatus::Completed,
            $statuses->contains(fn (VendorOrderStatus $status): bool => in_array($status, [VendorOrderStatus::Shipped, VendorOrderStatus::OutForDelivery, VendorOrderStatus::Delivered], true)) => OrderStatus::PartiallyShipped,
            $statuses->contains(fn (VendorOrderStatus $status): bool => in_array($status, [VendorOrderStatus::Accepted, VendorOrderStatus::Packed], true)) => OrderStatus::Processing,
            $order->payment_status === PaymentStatus::Paid => OrderStatus::Paid,
            default => $order->order_status,
        };

        if ($order->order_status !== $nextStatus) {
            $previousStatus = $order->order_status;

            $order->update([
                'order_status' => $nextStatus,
            ]);

            $this->recordStatusHistory(
                $order,
                $nextStatus->value,
                sprintf('Order status synced from vendor orders: %s -> %s.', $previousStatus->value, $nextStatus->value),
                $actor?->id ?? $order->user_id
            );

            $this->marketplaceNotificationService->sendOrderStatusChanged(
                $order->fresh($this->orderDetailRelations()),
                sprintf('Order status changed from %s to %s.', $previousStatus->value, $nextStatus->value)
            );
        }

        return $order->refresh();
    }

    private function recordStatusHistory(
        Order $order,
        string $status,
        ?string $message,
        ?int $changedBy,
        ?VendorOrder $vendorOrder = null,
    ): OrderStatusHistory {
        return OrderStatusHistory::query()->create([
            'order_id' => $order->id,
            'vendor_order_id' => $vendorOrder?->id,
            'status' => $status,
            'message' => $message,
            'changed_by' => $changedBy,
            'created_at' => now(),
        ]);
    }

    private function ensureAllowedVendorTransition(VendorOrderStatus $currentStatus, VendorOrderStatus $targetStatus): void
    {
        if ($currentStatus === $targetStatus) {
            return;
        }

        $allowedTransitions = [
            VendorOrderStatus::New->value => [VendorOrderStatus::Accepted, VendorOrderStatus::Cancelled],
            VendorOrderStatus::Accepted->value => [VendorOrderStatus::Packed, VendorOrderStatus::Cancelled],
            VendorOrderStatus::Packed->value => [VendorOrderStatus::Shipped, VendorOrderStatus::Cancelled],
            VendorOrderStatus::Shipped->value => [VendorOrderStatus::OutForDelivery, VendorOrderStatus::Delivered, VendorOrderStatus::Returned],
            VendorOrderStatus::OutForDelivery->value => [VendorOrderStatus::Delivered, VendorOrderStatus::Returned],
            VendorOrderStatus::Delivered->value => [VendorOrderStatus::Returned],
            VendorOrderStatus::Cancelled->value => [],
            VendorOrderStatus::Returned->value => [],
        ];

        $allowed = collect($allowedTransitions[$currentStatus->value] ?? [])
            ->map(fn (VendorOrderStatus $status): string => $status->value)
            ->all();

        if (! in_array($targetStatus->value, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => [
                    'The requested vendor order status transition is not allowed.',
                ],
            ]);
        }
    }

    private function orderListRelations(): array
    {
        return [
            'vendorOrders.vendor.vendorProfile',
            'payments' => fn ($query) => $query->latest('id'),
            'items',
        ];
    }

    private function orderDetailRelations(): array
    {
        return [
            'user',
            'address',
            'vendorOrders.vendor.vendorProfile',
            'vendorOrders.items',
            'items.vendor.vendorProfile',
            'payments' => fn ($query) => $query->latest('id'),
            'statusHistories.actor',
        ];
    }

    private function vendorOrderRelations(): array
    {
        return [
            'vendor.vendorProfile',
            'items',
            'order.user',
            'order.address',
            'order.payments' => fn ($query) => $query->latest('id'),
            'statusHistories.actor',
        ];
    }
}
