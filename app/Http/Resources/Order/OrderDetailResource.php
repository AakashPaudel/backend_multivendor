<?php

namespace App\Http\Resources\Order;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;

class OrderDetailResource extends ApiResource
{
    public function toArray(Request $request): array
    {
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
            'customer' => $this->whenLoaded('user', function (): array {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'phone' => $this->user->phone,
                ];
            }),
            'address' => $this->whenLoaded('address', function (): ?array {
                if (! $this->address) {
                    return null;
                }

                return [
                    'id' => $this->address->id,
                    'label' => $this->address->label,
                    'recipient_name' => $this->address->recipient_name,
                    'recipient_phone' => $this->address->recipient_phone,
                    'address_line_1' => $this->address->address_line_1,
                    'address_line_2' => $this->address->address_line_2,
                    'city' => $this->address->city,
                    'district' => $this->address->district,
                    'province' => $this->address->province,
                    'country' => $this->address->country,
                    'postal_code' => $this->address->postal_code,
                ];
            }),
            'payments' => $this->whenLoaded('payments', function () {
                return $this->payments->map(fn ($payment): array => [
                    'id' => $payment->id,
                    'payment_method' => $payment->payment_method,
                    'gateway' => $payment->gateway,
                    'amount' => $payment->amount,
                    'transaction_uuid' => $payment->transaction_uuid,
                    'gateway_reference' => $payment->gateway_reference,
                    'status' => $payment->status->value,
                    'verification_status' => $payment->verification_status->value,
                    'paid_at' => $payment->paid_at?->toISOString(),
                    'created_at' => $payment->created_at?->toISOString(),
                ])->values();
            }),
            'vendor_orders' => $this->whenLoaded('vendorOrders', fn () => VendorOrderResource::collection($this->vendorOrders)),
            'items' => $this->whenLoaded('items', fn () => OrderItemResource::collection($this->items)),
            'status_histories' => $this->whenLoaded('statusHistories', fn () => OrderStatusHistoryResource::collection($this->statusHistories)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
