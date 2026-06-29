<?php

namespace App\Http\Resources\Order;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;

class OrderItemResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'vendor_id' => $this->vendor_id,
            'product_name' => $this->product_name_snapshot,
            'sku' => $this->sku_snapshot,
            'unit_price' => $this->unit_price,
            'quantity' => $this->quantity,
            'line_total' => $this->line_total,
            'commission_amount' => $this->commission_amount,
            'net_amount' => $this->net_amount,
            'status' => $this->status,
            'vendor' => $this->whenLoaded('vendor', function (): array {
                return [
                    'id' => $this->vendor->id,
                    'name' => $this->vendor->name,
                    'store_name' => $this->vendor->vendorProfile?->store_name,
                ];
            }),
        ];
    }
}
