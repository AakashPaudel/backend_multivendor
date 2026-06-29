<?php

namespace App\Console\Commands;

use App\Services\Support\DemoMarketplaceService;
use Illuminate\Console\Command;

class PopulateDemoMarketplaceCommand extends Command
{
    protected $signature = 'demo:populate-marketplace
        {--categories=12 : Number of categories to create}
        {--vendors=12 : Number of vendors to create}
        {--customers=30 : Number of customers to create}
        {--orders=120 : Number of orders to create}
        {--products-min=6 : Minimum products per approved vendor}
        {--products-max=12 : Maximum products per approved vendor}
        {--reset : Truncate marketplace data before seeding demo data}';

    protected $description = 'Populate the application with realistic demo marketplace data for local development';

    public function __construct(
        private readonly DemoMarketplaceService $demoMarketplaceService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $summary = $this->demoMarketplaceService->populate([
            'categories' => (int) $this->option('categories'),
            'vendors' => (int) $this->option('vendors'),
            'customers' => (int) $this->option('customers'),
            'orders' => (int) $this->option('orders'),
            'products_min' => (int) $this->option('products-min'),
            'products_max' => (int) $this->option('products-max'),
            'reset' => (bool) $this->option('reset'),
        ], $this);

        if (($summary['success'] ?? false) !== true) {
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Demo marketplace data generated successfully.');
        $this->table(
            ['Metric', 'Count'],
            [
                ['categories', $summary['categories']],
                ['approved vendors', $summary['approved_vendors']],
                ['pending vendors', $summary['pending_vendors']],
                ['suspended vendors', $summary['suspended_vendors']],
                ['customers', $summary['customers']],
                ['products', $summary['products']],
                ['orders', $summary['orders']],
                ['paid payments', $summary['paid_payments']],
                ['initiated payments', $summary['initiated_payments']],
                ['pending review payments', $summary['pending_review_payments']],
                ['failed payments', $summary['failed_payments']],
                ['cancelled payments', $summary['cancelled_payments']],
            ]
        );

        $this->line('Admin login: admin@multi-vendor.local / password');

        return self::SUCCESS;
    }
}
