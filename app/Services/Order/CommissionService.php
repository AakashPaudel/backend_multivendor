<?php

namespace App\Services\Order;

use App\Enums\CommissionScope;
use App\Models\Commission;
use App\Models\User;
use App\Services\Service;
use Carbon\CarbonInterface;

class CommissionService extends Service
{
    public function resolveRateForVendor(User $vendor, ?CarbonInterface $at = null): float
    {
        $at ??= now();

        if ($vendor->vendorProfile?->commission_rate_override !== null) {
            return (float) $vendor->vendorProfile->commission_rate_override;
        }

        $vendorSpecific = Commission::query()
            ->where('scope', CommissionScope::VendorSpecific)
            ->where('vendor_id', $vendor->id)
            ->where(function ($query) use ($at): void {
                $query->whereNull('active_from')
                    ->orWhere('active_from', '<=', $at);
            })
            ->where(function ($query) use ($at): void {
                $query->whereNull('active_to')
                    ->orWhere('active_to', '>=', $at);
            })
            ->latest('active_from')
            ->latest('id')
            ->first();

        if ($vendorSpecific) {
            return (float) $vendorSpecific->rate;
        }

        $global = Commission::query()
            ->where('scope', CommissionScope::Global)
            ->whereNull('vendor_id')
            ->where(function ($query) use ($at): void {
                $query->whereNull('active_from')
                    ->orWhere('active_from', '<=', $at);
            })
            ->where(function ($query) use ($at): void {
                $query->whereNull('active_to')
                    ->orWhere('active_to', '>=', $at);
            })
            ->latest('active_from')
            ->latest('id')
            ->first();

        return (float) ($global?->rate ?? 0);
    }

    public function calculateBreakdown(float $subtotal, float $rate): array
    {
        $commissionAmount = round(($subtotal * $rate) / 100, 2);

        return [
            'commission_rate' => $rate,
            'commission_amount' => $commissionAmount,
            'net_amount' => round($subtotal - $commissionAmount, 2),
        ];
    }
}
