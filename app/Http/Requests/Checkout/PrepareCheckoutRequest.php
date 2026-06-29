<?php

namespace App\Http\Requests\Checkout;

use App\Http\Requests\ApiRequest;

class PrepareCheckoutRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'address_id' => ['required', 'integer', 'exists:addresses,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
