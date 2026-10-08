<?php

namespace App\Console\Commands;

use App\Services\GatewayPaymentService;
use Illuminate\Console\Command;

/**
 * Third safety net behind the ToyyibPay Return URL and Callback (see
 * bootstrap/app.php for the schedule). Catches a passenger who paid
 * successfully but closed the tab before being redirected back. Without this
 * the payment would sit as pending forever and the driver would never be
 * credited, even though the money really did arrive.
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
