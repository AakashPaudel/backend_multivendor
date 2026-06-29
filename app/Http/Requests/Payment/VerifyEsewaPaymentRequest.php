<?php

namespace App\Http\Requests\Payment;

use App\Http\Requests\ApiRequest;

class VerifyEsewaPaymentRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'order_number' => ['required', 'string', 'max:255'],
            'transaction_uuid' => ['nullable', 'string', 'max:255'],
        ];
    }
}
