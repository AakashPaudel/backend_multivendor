<?php

namespace App\Http\Requests\Cart;

use App\Http\Requests\ApiRequest;

class StoreCartItemRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
