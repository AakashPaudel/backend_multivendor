<?php

namespace App\Enums;

enum VendorOrderStatus: string
{
    case New = 'new';
    case Accepted = 'accepted';
    case Packed = 'packed';
    case Shipped = 'shipped';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Returned = 'returned';
}
