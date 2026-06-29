<?php

namespace App\Http\Resources\Order;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;

class OrderStatusHistoryResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'vendor_order_id' => $this->vendor_order_id,
            'status' => $this->status,
            'message' => $this->message,
            'changed_by' => $this->changed_by,
            'actor' => $this->whenLoaded('actor', function (): ?array {
                if (! $this->actor) {
                    return null;
                }

                return [
                    'id' => $this->actor->id,
                    'name' => $this->actor->name,
                    'role' => $this->actor->role->value,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
