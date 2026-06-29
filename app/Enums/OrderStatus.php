<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case PaymentInitiated = 'payment_initiated';
    case Paid = 'paid';
    case Processing = 'processing';
    case PartiallyShipped = 'partially_shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case Failed = 'failed';
}
