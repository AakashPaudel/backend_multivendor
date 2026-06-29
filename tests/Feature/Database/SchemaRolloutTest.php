<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchemaRolloutTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_phase_two_tables_exist(): void
    {
        $tables = [
            'users',
            'password_reset_tokens',
            'personal_access_tokens',
            'vendor_profiles',
            'customer_profiles',
            'addresses',
            'categories',
            'products',
            'product_images',
            'carts',
            'cart_items',
            'orders',
            'vendor_orders',
            'order_items',
            'payments',
            'order_status_histories',
            'platform_settings',
            'commissions',
            'recommendations',
            'audit_logs',
            'notifications',
            'jobs',
            'job_batches',
            'failed_jobs',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Failed asserting that table [{$table}] exists.");
        }
    }

    public function test_users_table_contains_marketplace_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('users', [
            'phone',
            'role',
            'status',
        ]));
    }
}
