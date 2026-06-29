<?php

namespace App\Http\Resources\Order;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;

class OrderResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        $latestPayment = $this->relationLoaded('payments') ? $this->payments->first() : null;

        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'user_id' => $this->user_id,
            'address_id' => $this->address_id,
            'subtotal' => $this->subtotal,
            'discount_total' => $this->discount_total,
            'shipping_total' => $this->shipping_total,
            'tax_total' => $this->tax_total,
            'grand_total' => $this->grand_total,
            'payment_status' => $this->payment_status->value,
            'order_status' => $this->order_status->value,
            'notes' => $this->notes,
            'placed_at' => $this->placed_at?->toISOString(),
            'items_count' => $this->whenCounted('items'),
            'vendor_orders_count' => $this->whenCounted('vendorOrders'),
            'latest_payment' => $latestPayment ? [
                'id' => $latestPayment->id,
                'status' => $latestPayment->status->value,
                'verification_status' => $latestPayment->verification_status->value,
                'gateway_reference' => $latestPayment->gateway_reference,
                'transaction_uuid' => $latestPayment->transaction_uuid,
                'paid_at' => $latestPayment->paid_at?->toISOString(),
            ] : null,
            'vendor_orders' => $this->whenLoaded('vendorOrders', fn () => VendorOrderResource::collection($this->vendorOrders)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
