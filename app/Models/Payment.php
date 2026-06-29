<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\PaymentVerificationStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'payment_method',
        'gateway',
        'amount',
        'transaction_uuid',
        'gateway_reference',
        'status',
        'verification_status',
        'raw_request_json',
        'raw_response_json',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PaymentStatus::class,
            'verification_status' => PaymentVerificationStatus::class,
            'raw_request_json' => 'array',
            'raw_response_json' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::Pending);
    }

    public function scopeInitiated(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::Initiated);
    }
}
