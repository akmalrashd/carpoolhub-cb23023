<?php

namespace App\Console\Commands;

use App\Services\ToyyibPayService;
use Illuminate\Console\Command;

/**
 * One-off setup: creates a ToyyibPay Category for this app and prints the
 * code to paste into .env as TOYYIBPAY_CATEGORY_CODE. Run once per
 * environment/secret key — a sandbox account and a live account each need
 * their own category, since they're entirely separate ToyyibPay accounts.
 */
class ToyyibPayCreateCategory extends Command
{
    protected $signature = 'toyyibpay:create-category';

    protected $description = 'Create a ToyyibPay Category for this app and print its code';

    public function handle(ToyyibPayService $toyyibPayService): int
    {
        $secretKey = config('services.toyyibpay.secret_key');

        if (empty($secretKey)) {
            $this->error('TOYYIBPAY_SECRET_KEY must be set in .env first.');

            return self::FAILURE;
        }

        $categoryCode = $toyyibPayService->createCategory(
            'CarpoolHub Trip Payments',
            'Trip payments collected from passengers via CarpoolHub'
        );

        if (! $categoryCode) {
            $this->error('ToyyibPay did not return a category code — check storage/logs/laravel.log for details.');

            return self::FAILURE;
        }

        $this->info("Category created: {$categoryCode}");
        $this->info('Paste this into .env as: TOYYIBPAY_CATEGORY_CODE='.$categoryCode);

        return self::SUCCESS;
    }
}
