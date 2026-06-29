<?php

namespace App\Services\Admin;

use App\Enums\CommissionScope;
use App\Models\Commission;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\VendorProfile;
use App\Services\Service;
use App\Services\Support\AuditLogService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminSettingsService extends Service
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function updateSettings(User $actor, array $attributes): array
    {
        $settings = DB::transaction(function () use ($actor, $attributes): array {
            foreach ($attributes['settings'] as $setting) {
                $model = PlatformSetting::query()->updateOrCreate(
                    ['key' => $setting['key']],
                    ['value' => $setting['value'] ?? []]
                );

                $this->auditLogService->record($actor, 'platform.setting_updated', $model, [
                    'key' => $setting['key'],
                ]);
            }

            return PlatformSetting::query()->orderBy('key')->get()->all();
        });

        Cache::forget('admin:settings');
        Cache::forget('admin:dashboard');

        return $settings;
    }

    public function commissionSnapshot(): array
    {
        return [
            'global_rate' => $this->resolveGlobalRate(),
            'vendor_overrides' => VendorProfile::query()
                ->with('user')
                ->whereNotNull('commission_rate_override')
                ->orderBy('id')
                ->get()
                ->map(fn (VendorProfile $profile): array => [
                    'vendor_id' => $profile->user_id,
                    'store_name' => $profile->store_name,
                    'vendor_name' => $profile->user?->name,
                    'rate' => number_format((float) $profile->commission_rate_override, 2, '.', ''),
                ])
                ->all(),
        ];
    }

    public function updateCommissions(User $actor, array $attributes): array
    {
        DB::transaction(function () use ($actor, $attributes): void {
            Commission::query()->updateOrCreate(
                [
                    'scope' => CommissionScope::Global,
                    'vendor_id' => null,
                ],
                [
                    'rate' => $attributes['global_rate'],
                    'active_from' => now(),
                    'active_to' => null,
                ]
            );

            foreach ($attributes['vendor_overrides'] ?? [] as $override) {
                $profile = VendorProfile::query()
                    ->where('user_id', $override['vendor_id'])
                    ->first();

                if (! $profile) {
                    continue;
                }

                $profile->update([
                    'commission_rate_override' => $override['rate'],
                ]);

                $this->auditLogService->record($actor, 'vendor.commission_override_updated', $profile, [
                    'vendor_id' => $override['vendor_id'],
                    'rate' => $override['rate'],
                ]);
            }
        });

        Cache::forget('admin:dashboard');

        return $this->commissionSnapshot();
    }

    private function resolveGlobalRate(): string
    {
        $global = Commission::query()
            ->where('scope', CommissionScope::Global)
            ->whereNull('vendor_id')
            ->latest('active_from')
            ->latest('id')
            ->first();

        return number_format((float) ($global?->rate ?? 0), 2, '.', '');
    }
}
