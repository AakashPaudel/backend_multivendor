<?php

namespace App\Http\Resources\Payment;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;

class PaymentInitiationResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'order_number' => $this->resource['order']->order_number,
            'payment' => [
                'id' => $this->resource['payment']->id,
                'transaction_uuid' => $this->resource['payment']->transaction_uuid,
                'status' => $this->resource['payment']->status->value,
                'verification_status' => $this->resource['payment']->verification_status->value,
                'amount' => $this->resource['payment']->amount,
            ],
            'esewa' => $this->resource['payload'],
        ];
    }
}
