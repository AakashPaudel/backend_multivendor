<?php

namespace App\Services\Admin;

use App\Enums\VendorApprovalStatus;
use App\Models\User;
use App\Models\VendorProfile;
use App\Services\Service;
use App\Services\Support\AuditLogService;
use App\Services\Support\MarketplaceNotificationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class VendorApprovalService extends Service
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly MarketplaceNotificationService $marketplaceNotificationService,
    ) {}

    public function paginate(): LengthAwarePaginator
    {
        return VendorProfile::query()
            ->with('user')
            ->latest('id')
            ->paginate();
    }

    public function approve(User $actor, VendorProfile $profile): VendorProfile
    {
        return $this->transition($actor, $profile, VendorApprovalStatus::Approved);
    }

    public function reject(User $actor, VendorProfile $profile, ?string $reason): VendorProfile
    {
        return $this->transition($actor, $profile, VendorApprovalStatus::Rejected, $reason);
    }

    public function suspend(User $actor, VendorProfile $profile, ?string $reason): VendorProfile
    {
        return $this->transition($actor, $profile, VendorApprovalStatus::Suspended, $reason);
    }

    protected function transition(User $actor, VendorProfile $profile, VendorApprovalStatus $status, ?string $reason = null): VendorProfile
    {
        return DB::transaction(function () use ($actor, $profile, $reason, $status): VendorProfile {
            $profile->forceFill([
                'approval_status' => $status,
                'approved_by' => $actor->id,
                'approved_at' => $status === VendorApprovalStatus::Approved ? now() : null,
                'rejection_reason' => $status === VendorApprovalStatus::Approved ? null : $reason,
            ])->save();

            $this->auditLogService->record($actor, 'vendor.approval_status_changed', $profile, [
                'approval_status' => $status->value,
                'reason' => $reason,
            ]);

            $this->marketplaceNotificationService->sendVendorDecision($profile);

            return $profile->fresh('user');
        });
    }
}
