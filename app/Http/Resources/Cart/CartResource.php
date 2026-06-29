<?php

namespace App\Http\Resources\Cart;

use App\Http\Resources\ApiResource;

class CartResource extends ApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource['cart']->id,
            'items' => CartItemResource::collection(collect($this->resource['items'])),
            'totals' => $this->resource['totals'],
        ];
    }
}
