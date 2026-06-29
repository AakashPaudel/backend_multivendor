<?php

namespace App\Models;

use App\Enums\UserStatus;
use App\Enums\VendorApprovalStatus;
use Database\Factories\VendorProfileFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorProfile extends Model
{
    /** @use HasFactory<VendorProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'store_name',
        'slug',
        'description',
        'logo_path',
        'banner_path',
        'business_email',
        'business_phone',
        'address_line',
        'city',
        'district',
        'country',
        'approval_status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'commission_rate_override',
    ];

    protected function casts(): array
    {
        return [
            'approval_status' => VendorApprovalStatus::class,
            'approved_at' => 'datetime',
            'commission_rate_override' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'vendor_id', 'user_id');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approval_status', VendorApprovalStatus::Approved);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('approval_status', VendorApprovalStatus::Pending);
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->where('approval_status', VendorApprovalStatus::Approved)
            ->whereHas('user', fn (Builder $userQuery) => $userQuery->where('status', UserStatus::Active));
    }

    public function isApproved(): bool
    {
        return $this->approval_status === VendorApprovalStatus::Approved;
    }
}
