<?php

namespace App\Http\Resources\Payment;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;

class PaymentStatusResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'message' => $this->resource['message'],
            'idempotent' => $this->resource['idempotent'],
            'verification' => [
                'status' => $this->resource['verification']['status'],
                'verified' => $this->resource['verification']['verified'],
                'reason' => $this->resource['verification']['reason'],
            ],
            'order' => [
                'id' => $this->resource['order']->id,
                'order_number' => $this->resource['order']->order_number,
                'payment_status' => $this->resource['order']->payment_status->value,
                'order_status' => $this->resource['order']->order_status->value,
                'grand_total' => $this->resource['order']->grand_total,
            ],
            'payment' => [
                'id' => $this->resource['payment']->id,
                'gateway' => $this->resource['payment']->gateway,
                'payment_method' => $this->resource['payment']->payment_method,
                'transaction_uuid' => $this->resource['payment']->transaction_uuid,
                'gateway_reference' => $this->resource['payment']->gateway_reference,
                'amount' => $this->resource['payment']->amount,
                'status' => $this->resource['payment']->status->value,
                'verification_status' => $this->resource['payment']->verification_status->value,
                'paid_at' => $this->resource['payment']->paid_at?->toIso8601String(),
            ],
        ];
    }
}
