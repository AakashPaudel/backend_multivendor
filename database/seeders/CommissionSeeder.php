<?php

namespace Database\Seeders;

use App\Enums\CommissionScope;
use App\Models\Commission;
use Illuminate\Database\Seeder;

class CommissionSeeder extends Seeder
{
    public function run(): void
    {
        Commission::query()->updateOrCreate(
            [
                'scope' => CommissionScope::Global,
                'vendor_id' => null,
            ],
            [
                'rate' => 10.00,
                'active_from' => now(),
                'active_to' => null,
            ]
        );
    }
}
