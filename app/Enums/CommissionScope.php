<?php

namespace App\Enums;

enum CommissionScope: string
{
    case Global = 'global';
    case VendorSpecific = 'vendor_specific';
}
