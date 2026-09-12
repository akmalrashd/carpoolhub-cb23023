<?php

namespace App\Console\Commands;

use App\Services\GatewayPaymentService;
use Illuminate\Console\Command;

/**
 * Third safety net behind the ToyyibPay Return URL and Callback (see
 * bootstrap/app.php for the schedule). Catches a passenger who paid
 * successfully but closed the tab before the Return URL redirect fired —
 * without this, that payment would stay 'pending' forever with the driver
 * never credited, despite the money having actually arrived.
 */
class ReconcileGatewayTransactions extends Command
{
    protected $signature = 'payments:reconcile-gateway-transactions';

    protected $description = 'Re-check any stuck-pending ToyyibPay transaction against the gateway and finalize it';

    public function handle(GatewayPaymentService $gatewayPaymentService): int
    {
        $results = $gatewayPaymentService->reconcilePending();

        $this->info("Checked {$results['checked']}, finalized {$results['finalized']}, expired {$results['expired']}.");

        return self::SUCCESS;
    }
}
