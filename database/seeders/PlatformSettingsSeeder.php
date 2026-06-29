<?php

namespace Database\Seeders;

use App\Models\PlatformSetting;
use Illuminate\Database\Seeder;

class PlatformSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'platform.name' => ['value' => ['name' => 'Multi Vendor Ecommerce']],
            'platform.currency' => ['value' => ['code' => 'NPR']],
            'platform.support_email' => ['value' => ['email' => 'support@multi-vendor.local']],
            'payment.esewa.mode' => ['value' => ['mode' => 'sandbox']],
        ];

        foreach ($settings as $key => $payload) {
            PlatformSetting::query()->updateOrCreate(['key' => $key], $payload);
        }
    }
}
