<?php

namespace App\Enums;

enum PaymentVerificationStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Failed = 'failed';
    case Mismatch = 'mismatch';
}
