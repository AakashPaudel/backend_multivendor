<?php

namespace App\Http\Requests\Payment;

use App\Http\Requests\ApiRequest;

class InitiateEsewaPaymentRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'order_number' => ['required', 'string', 'max:255'],
        ];
    }
}
