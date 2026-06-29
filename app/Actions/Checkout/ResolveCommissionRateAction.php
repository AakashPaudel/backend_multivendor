<?php

namespace App\Actions\Checkout;

use App\Enums\CommissionScope;
use App\Models\Commission;
use App\Models\User;

class ResolveCommissionRateAction
{
    public function handle(User $vendor): string
    {
        $overrideRate = $vendor->vendorProfile?->commission_rate_override;

        if ($overrideRate !== null) {
            return number_format((float) $overrideRate, 2, '.', '');
        }

        $activeAt = now();

        $vendorSpecificRate = Commission::query()
            ->where('scope', CommissionScope::VendorSpecific)
            ->where('vendor_id', $vendor->id)
            ->where(function ($query) use ($activeAt): void {
                $query->whereNull('active_from')
                    ->orWhere('active_from', '<=', $activeAt);
            })
            ->where(function ($query) use ($activeAt): void {
                $query->whereNull('active_to')
                    ->orWhere('active_to', '>=', $activeAt);
            })
            ->latest('active_from')
            ->value('rate');

        if ($vendorSpecificRate !== null) {
            return number_format((float) $vendorSpecificRate, 2, '.', '');
        }

        $globalRate = Commission::query()
            ->where('scope', CommissionScope::Global)
            ->whereNull('vendor_id')
            ->where(function ($query) use ($activeAt): void {
                $query->whereNull('active_from')
                    ->orWhere('active_from', '<=', $activeAt);
            })
            ->where(function ($query) use ($activeAt): void {
                $query->whereNull('active_to')
                    ->orWhere('active_to', '>=', $activeAt);
            })
            ->latest('active_from')
            ->value('rate');

        return number_format((float) ($globalRate ?? 0), 2, '.', '');
    }
}
