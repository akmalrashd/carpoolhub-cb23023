<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\BillingCycleService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('billing-cycles:auto-close', function (BillingCycleService $billingCycleService) {
    $closedCount = $billingCycleService->autoCloseOverdueCycles();

    if ($closedCount > 0) {
        $this->info("Auto-close completed. Closed {$closedCount} overdue billing cycle(s).");
    } else {
        $this->info('No overdue open billing cycle found.');
    }
})->purpose('Auto-close overdue open billing cycles and ensure next open cycle exists.');

Schedule::command('billing-cycles:auto-close')
    ->dailyAt('00:05')
    ->timezone(config('app.timezone', 'UTC'))
    ->withoutOverlapping();
