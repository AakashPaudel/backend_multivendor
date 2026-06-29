<?php

use App\Console\Commands\PopulateDemoMarketplaceCommand;
use App\Services\Payment\PendingPaymentMaintenanceService;
use App\Services\Recommendation\RecommendationService;
use App\Services\Support\CacheInvalidationService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('recommendations:generate {--limit=8}', function () {
    $count = app(RecommendationService::class)
        ->generate((int) $this->option('limit'));

    $this->info(sprintf('Generated %d recommendation rows.', $count));
})->purpose('Generate product recommendations from paid order history');

Artisan::command('payments:cleanup-stale-pending {--hours=24}', function () {
    $count = app(PendingPaymentMaintenanceService::class)
        ->cleanupStalePending((int) $this->option('hours'));

    $this->info(sprintf('Cleaned up %d stale pending payments.', $count));
})->purpose('Mark stale pending payments as failed after a configured cutoff');

Artisan::command('payments:reconcile-pending {--hours=1} {--limit=25}', function () {
    $summary = app(PendingPaymentMaintenanceService::class)
        ->reconcilePending((int) $this->option('hours'), (int) $this->option('limit'));

    $this->info(sprintf(
        'Processed %d pending payments, resolved %d, failed %d.',
        $summary['processed'],
        $summary['resolved'],
        $summary['failed']
    ));
})->purpose('Reconcile older pending payments against the payment gateway');

app(Kernel::class)->addCommands([
    PopulateDemoMarketplaceCommand::class,
]);

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('recommendations:generate --limit=8')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('payments:cleanup-stale-pending --hours=24')
    ->everySixHours()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('payments:reconcile-pending --hours=1 --limit=25')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::call(function (): void {
    Artisan::call('queue:prune-failed', [
        '--hours' => 48,
    ]);

    app(CacheInvalidationService::class)->forgetAdminDashboard();
})->name('queue:prune-failed-cache-refresh')->daily()->onOneServer();

Schedule::call(function (): void {
    Log::info('scheduler.heartbeat', [
        'timestamp' => now()->toIso8601String(),
    ]);
})->everyFiveMinutes();
