<?php

namespace App\Http\Resources\Checkout;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;

class CheckoutPreparationResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'order' => [
                'id' => $this->resource['order']->id,
                'order_number' => $this->resource['order']->order_number,
                'subtotal' => $this->resource['order']->subtotal,
                'discount_total' => $this->resource['order']->discount_total,
                'shipping_total' => $this->resource['order']->shipping_total,
                'tax_total' => $this->resource['order']->tax_total,
                'grand_total' => $this->resource['order']->grand_total,
                'payment_status' => $this->resource['order']->payment_status->value,
                'order_status' => $this->resource['order']->order_status->value,
            ],
            'totals' => $this->resource['totals'],
            'vendor_orders' => collect($this->resource['vendor_orders'])->map(fn (array $vendorOrder): array => [
                'id' => $vendorOrder['id'],
                'vendor_id' => $vendorOrder['vendor_id'],
                'store_name' => $vendorOrder['store_name'],
                'subtotal' => $vendorOrder['subtotal'],
                'commission_rate' => $vendorOrder['commission_rate'],
                'commission_amount' => $vendorOrder['commission_amount'],
                'net_amount' => $vendorOrder['net_amount'],
                'items_count' => $vendorOrder['items_count'],
            ])->values(),
            'payment' => [
                'id' => $this->resource['payment']->id,
                'gateway' => $this->resource['payment']->gateway,
                'payment_method' => $this->resource['payment']->payment_method,
                'amount' => $this->resource['payment']->amount,
                'status' => $this->resource['payment']->status->value,
                'verification_status' => $this->resource['payment']->verification_status->value,
                'transaction_uuid' => $this->resource['payment']->transaction_uuid,
            ],
            'payment_initiation' => $this->resource['payment_initiation'],
        ];
    }
}
