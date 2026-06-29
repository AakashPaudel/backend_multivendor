<?php

namespace App\Http\Resources\Order;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;

class VendorOrderResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'vendor_id' => $this->vendor_id,
            'subtotal' => $this->subtotal,
            'commission_amount' => $this->commission_amount,
            'net_amount' => $this->net_amount,
            'status' => $this->status->value,
            'vendor' => $this->whenLoaded('vendor', function (): array {
                return [
                    'id' => $this->vendor->id,
                    'name' => $this->vendor->name,
                    'store_name' => $this->vendor->vendorProfile?->store_name,
                ];
            }),
            'order' => $this->whenLoaded('order', function (): array {
                $latestPayment = $this->order->payments->first();

                return [
                    'id' => $this->order->id,
                    'order_number' => $this->order->order_number,
                    'order_status' => $this->order->order_status->value,
                    'payment_status' => $this->order->payment_status->value,
                    'grand_total' => $this->order->grand_total,
                    'placed_at' => $this->order->placed_at?->toISOString(),
                    'customer' => $this->order->relationLoaded('user') ? [
                        'id' => $this->order->user->id,
                        'name' => $this->order->user->name,
                        'email' => $this->order->user->email,
                    ] : null,
                    'payment' => $latestPayment ? [
                        'status' => $latestPayment->status->value,
                        'verification_status' => $latestPayment->verification_status->value,
                        'gateway_reference' => $latestPayment->gateway_reference,
                        'paid_at' => $latestPayment->paid_at?->toISOString(),
                    ] : null,
                ];
            }),
            'items' => $this->whenLoaded('items', fn () => OrderItemResource::collection($this->items)),
            'status_histories' => $this->whenLoaded('statusHistories', fn () => OrderStatusHistoryResource::collection($this->statusHistories)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
