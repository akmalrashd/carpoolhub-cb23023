<?php

namespace App\Services;

use App\Models\GatewayTransaction;
use App\Models\SystemSetting;
use App\Models\TripPayment;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Concerns\FormatsTripLabel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Orchestrates the ToyyibPay checkout flow and its idempotency. Three
 * callers can each finalize the same gateway_transactions row — the Return
 * URL redirect (works on localhost), the server-to-server Callback (cannot
 * reach localhost per ToyyibPay's own docs, but is the authoritative path
 * once this app is deployed publicly), and a scheduled reconciliation job
 * (catches a passenger who closes the tab before the Return URL fires) —
 * and all three converge on finalize()'s row lock, so whichever gets there
 * first does the real work and the other two are no-ops.
 */
class GatewayPaymentService
{
    use FormatsTripLabel;

    public function __construct(
        private readonly ToyyibPayService $toyyibPayService,
        private readonly PaymentService $paymentService,
        private readonly WalletService $walletService,
    ) {}

    public function createTransactionAndRedirectUrl(User $payer, TripPayment $payment): string
    {
        $payment->loadMissing('trip.driver');

        if ($payment->user_id !== $payer->id) {
            throw ValidationException::withMessages(['payment' => 'You can only pay your own payment.']);
        }

        if ($payment->payment_status === TripPayment::STATUS_PAID) {
            throw ValidationException::withMessages(['payment' => 'Payment has already been confirmed as paid.']);
        }

        if ($payment->trip && $payment->trip->status === 'draft') {
            throw ValidationException::withMessages(['payment' => 'Draft trips do not require payment yet.']);
        }

        $this->ensurePaymentWindowOpen($payment);

        if (! $this->toyyibPayService->isConfigured()) {
            throw ValidationException::withMessages(['payment' => 'Online payment is not available right now.']);
        }

        $driver = $payment->trip?->driver;
        if (! $driver) {
            throw ValidationException::withMessages(['payment' => 'This trip has no assigned driver to pay.']);
        }

        // Reuse a still-live bill rather than spawning a duplicate if the
        // passenger closed and reopened the modal.
        $existing = GatewayTransaction::query()
            ->where('trip_payment_id', $payment->id)
            ->where('status', GatewayTransaction::STATUS_PENDING)
            ->whereNotNull('bill_code')
            ->where('created_at', '>=', now()->subDay())
            ->latest('id')
            ->first();

        if ($existing) {
            return $this->toyyibPayService->checkoutUrl($existing->bill_code);
        }

        $amountDue = round((float) $payment->amount_due, 2);
        $fee = $this->computeFee($amountDue);
        $orderId = 'GW-'.Str::uuid()->toString();
        $label = $payment->trip ? $this->formatTripLabel($payment->trip) : 'Trip #'.$payment->trip_id;

        $txn = GatewayTransaction::create([
            'gateway' => 'toyyibpay',
            'environment' => $this->environment(),
            'trip_payment_id' => $payment->id,
            'trip_id' => $payment->trip_id,
            'payer_id' => $payer->id,
            'driver_id' => $driver->id,
            'order_id' => $orderId,
            'amount_due' => $amountDue,
            'gateway_fee' => $fee,
            'amount_charged' => round($amountDue + $fee, 2),
            'status' => GatewayTransaction::STATUS_PENDING,
        ]);

        return $this->issueBill($txn, "Trip payment for {$label}", $payer);
    }

    /**
     * Combines every selected TripPayment into ONE ToyyibPay bill — one
     * gateway fee for the whole batch instead of one per trip, which is the
     * entire point of offering this from the bulk "Mark Selected as Paid"
     * action. Only sensible (and only offered) when every selected payment
     * is owed to the SAME driver: a single bill can only settle to one
     * wallet, and splitting one payment across several drivers' wallets
     * would need per-line allocation the UI doesn't attempt to show.
     *
     * @param  array<int, int>  $paymentIds
     */
    public function createBulkTransactionAndRedirectUrl(User $payer, array $paymentIds): string
    {
        $payments = TripPayment::query()
            ->whereIn('id', $paymentIds)
            ->with('trip.driver')
            ->get();

        if ($payments->isEmpty()) {
            throw ValidationException::withMessages(['payment' => 'No payments selected.']);
        }

        $driverIds = [];
        foreach ($payments as $payment) {
            if ($payment->user_id !== $payer->id) {
                throw ValidationException::withMessages(['payment' => 'You can only pay your own payments.']);
            }
            if ($payment->payment_status === TripPayment::STATUS_PAID) {
                throw ValidationException::withMessages(['payment' => 'One of the selected payments has already been paid.']);
            }
            if ($payment->trip && $payment->trip->status === 'draft') {
                throw ValidationException::withMessages(['payment' => 'Draft trips do not require payment yet.']);
            }
            $this->ensurePaymentWindowOpen($payment);
            $driverIds[(int) ($payment->trip?->driver_id ?? 0)] = true;
        }

        if (count($driverIds) > 1) {
            throw ValidationException::withMessages(['payment' => 'Online payment can only combine payments owed to the same driver.']);
        }

        if (! $this->toyyibPayService->isConfigured()) {
            throw ValidationException::withMessages(['payment' => 'Online payment is not available right now.']);
        }

        $driver = $payments->first()->trip?->driver;
        if (! $driver) {
            throw ValidationException::withMessages(['payment' => 'These trips have no assigned driver to pay.']);
        }

        $amountDue = round((float) $payments->sum(fn (TripPayment $p) => (float) $p->amount_due), 2);
        $fee = $this->computeFee($amountDue);
        $orderId = 'GW-'.Str::uuid()->toString();
        $lines = $payments->map(fn (TripPayment $p) => [
            'trip_payment_id' => $p->id,
            'amount' => round((float) $p->amount_due, 2),
        ])->values()->all();

        $txn = GatewayTransaction::create([
            'gateway' => 'toyyibpay',
            'environment' => $this->environment(),
            'trip_payment_id' => null,
            'trip_payment_ids' => $lines,
            'trip_id' => null,
            'payer_id' => $payer->id,
            'driver_id' => $driver->id,
            'order_id' => $orderId,
            'amount_due' => $amountDue,
            'gateway_fee' => $fee,
            'amount_charged' => round($amountDue + $fee, 2),
            'status' => GatewayTransaction::STATUS_PENDING,
        ]);

        return $this->issueBill($txn, count($payments).' trip payments to '.$driver->name, $payer);
    }

    /**
     * Shared by the single- and bulk-payment paths: call ToyyibPay, persist
     * the bill code or the failure, and return the checkout URL.
     */
    private function issueBill(GatewayTransaction $txn, string $description, User $payer): string
    {
        $billCode = $this->toyyibPayService->createBill([
            'amount_rm' => (float) $txn->amount_charged,
            'bill_name' => 'CarpoolHub Trip Payment',
            'bill_description' => $description,
            'external_reference_no' => $txn->order_id,
            'return_url' => route('payments.gateway.return'),
            'callback_url' => route('payments.gateway.callback'),
            'payer_name' => $payer->name,
            'payer_email' => $payer->email,
            'payer_phone' => $payer->phone ?: '0000000000',
        ]);

        if (! $billCode) {
            $txn->update([
                'status' => GatewayTransaction::STATUS_ERROR,
                'error_message' => 'ToyyibPay createBill returned no bill code.',
            ]);

            throw ValidationException::withMessages([
                'payment' => 'Could not start online payment right now — please try direct bank transfer instead.',
            ]);
        }

        $txn->update(['bill_code' => $billCode]);

        return $this->toyyibPayService->checkoutUrl($billCode);
    }

    /**
     * @return array{success: bool, trip_payment_id: ?int, is_bulk: bool}
     */
    public function handleReturn(?string $billCode): array
    {
        if (! $billCode) {
            return ['success' => false, 'trip_payment_id' => null, 'is_bulk' => false];
        }

        $txn = GatewayTransaction::where('bill_code', $billCode)->first();
        if (! $txn) {
            return ['success' => false, 'trip_payment_id' => null, 'is_bulk' => false];
        }

        if (! $txn->isPending()) {
            return $this->returnResult($txn);
        }

        // The Return URL's own query-string status is unsigned and forgeable
        // (anyone could hit this URL with an arbitrary status_id) — pull the
        // authoritative status from ToyyibPay's API instead of trusting it.
        $remote = $this->toyyibPayService->getBillTransactions($billCode);
        $status = $this->normalizeStatus($remote['billpaymentStatus'] ?? null);
        $refno = $remote['billpaymentInvoiceNo'] ?? null;

        $txn = $this->finalize($txn, $status, $refno, 'return_url', $remote);

        return $this->returnResult($txn);
    }

    /**
     * @return array{success: bool, trip_payment_id: ?int, is_bulk: bool}
     */
    private function returnResult(GatewayTransaction $txn): array
    {
        return [
            'success' => $txn->status === GatewayTransaction::STATUS_PAID,
            'trip_payment_id' => $txn->trip_payment_id,
            'is_bulk' => empty($txn->trip_payment_id) && ! empty($txn->trip_payment_ids),
        ];
    }

    /**
     * Never throws — an unverifiable or malformed callback is logged and
     * ignored, not fatal, matching TelegramController::webhook()'s "ack
     * fast, retry-storm otherwise" reasoning for the controller that calls
     * this.
     */
    public function handleCallback(array $payload): void
    {
        try {
            if (! $this->toyyibPayService->verifyCallbackHash($payload)) {
                Log::warning('ToyyibPay callback hash mismatch', ['order_id' => $payload['order_id'] ?? null]);

                return;
            }

            $txn = GatewayTransaction::where('bill_code', $payload['billcode'] ?? null)->first()
                ?? GatewayTransaction::where('order_id', $payload['order_id'] ?? null)->first();

            if (! $txn) {
                Log::warning('ToyyibPay callback for unknown transaction', [
                    'bill_code' => $payload['billcode'] ?? null,
                    'order_id' => $payload['order_id'] ?? null,
                ]);

                return;
            }

            if (! $txn->isPending()) {
                return;
            }

            // The hash IS the proof here, unlike the Return URL — use the
            // callback's own fields directly, no secondary API call needed.
            $status = $this->normalizeStatus($payload['status'] ?? null);
            $this->finalize($txn, $status, $payload['refno'] ?? null, 'callback', $payload);
        } catch (Throwable $e) {
            Log::error('ToyyibPay callback handling failed: '.$e->getMessage());
        }
    }

    /**
     * Third safety net for a passenger who closes the tab before the Return
     * URL redirect even fires. The $olderThanMinutes floor gives the Return
     * URL/Callback a fair chance to finalize normally first.
     *
     * @return array{checked: int, finalized: int, expired: int}
     */
    public function reconcilePending(int $olderThanMinutes = 5): array
    {
        $results = ['checked' => 0, 'finalized' => 0, 'expired' => 0];

        $stuck = GatewayTransaction::query()
            ->where('status', GatewayTransaction::STATUS_PENDING)
            ->whereNotNull('bill_code')
            ->where('created_at', '<=', now()->subMinutes($olderThanMinutes))
            ->get();

        foreach ($stuck as $txn) {
            $results['checked']++;

            $remote = $this->toyyibPayService->getBillTransactions($txn->bill_code);
            $status = $this->normalizeStatus($remote['billpaymentStatus'] ?? null);

            if ($status === 'unknown') {
                // Bill's own 1-day expiry window has long passed with still no
                // transaction record at all — treat as abandoned rather than
                // checking forever.
                if ($txn->created_at->lt(now()->subDay())) {
                    $txn->update([
                        'status' => GatewayTransaction::STATUS_EXPIRED,
                        'finalized_via' => 'reconciliation_job',
                        'finalized_at' => now(),
                    ]);
                    $results['expired']++;
                }

                continue;
            }

            $this->finalize($txn->fresh(), $status, $remote['billpaymentInvoiceNo'] ?? null, 'reconciliation_job', $remote);
            $results['finalized']++;
        }

        return $results;
    }

    /**
     * The single lock-guarded core all three callers above converge on.
     * Whichever acquires the lock first while status is still 'pending' does
     * the real work; every other caller sees a non-pending status inside the
     * lock and returns immediately as a no-op — this IS the idempotency
     * guard covering webhook retries and Return-URL/Callback races.
     */
    private function finalize(GatewayTransaction $txn, string $status, ?string $refno, string $via, ?array $rawPayload = null): GatewayTransaction
    {
        return DB::transaction(function () use ($txn, $status, $refno, $via, $rawPayload): GatewayTransaction {
            $locked = GatewayTransaction::whereKey($txn->id)->lockForUpdate()->first();

            if (! $locked || ! $locked->isPending()) {
                return $locked ?? $txn;
            }

            if ($status !== 'success') {
                $locked->update([
                    'raw_callback_payload' => $rawPayload,
                    'status' => $status === 'failed' ? GatewayTransaction::STATUS_FAILED : $locked->status,
                    'finalized_via' => $status === 'failed' ? $via : null,
                    'finalized_at' => $status === 'failed' ? now() : null,
                ]);

                return $locked;
            }

            $locked->update([
                'status' => GatewayTransaction::STATUS_PAID,
                'toyyibpay_refno' => $refno,
                'amount_credited' => $locked->amount_due,
                'raw_callback_payload' => $rawPayload,
                'finalized_via' => $via,
                'finalized_at' => now(),
            ]);

            if ($locked->trip_payment_id) {
                $payment = $locked->tripPayment
                    ?? TripPayment::where('trip_id', $locked->trip_id)->where('user_id', $locked->payer_id)->first();

                if ($payment) {
                    $this->paymentService->confirmPaidViaGateway($payment, 'toyyibpay', $refno);
                } else {
                    Log::warning('ToyyibPay payment finalized but trip_payment link is gone', ['gateway_transaction_id' => $locked->id]);
                }
            } elseif (! empty($locked->trip_payment_ids)) {
                // Bulk bill — confirm every line individually. A line whose
                // trip_payment row was hard-deleted since (a trip edit) is
                // skipped for confirmation, but the wallet is still credited
                // below for the full snapshotted total regardless — the
                // money is real either way.
                foreach ($locked->trip_payment_ids as $line) {
                    $linePayment = TripPayment::find($line['trip_payment_id'] ?? null);
                    if ($linePayment) {
                        $this->paymentService->confirmPaidViaGateway($linePayment, 'toyyibpay', $refno);
                    } else {
                        Log::warning('ToyyibPay bulk payment finalized but a trip_payment line is gone', [
                            'gateway_transaction_id' => $locked->id,
                            'trip_payment_id' => $line['trip_payment_id'] ?? null,
                        ]);
                    }
                }
            }

            // Credit the wallet even if the trip_payment link above was lost
            // to a trip edit — the money is real regardless of whether our
            // own bookkeeping row for it survived.
            $driver = $locked->driver ?? User::find($locked->driver_id);
            if ($driver) {
                $this->walletService->credit(
                    $driver,
                    (float) $locked->amount_due,
                    WalletTransaction::REASON_GATEWAY_PAYMENT,
                    'gateway_transaction',
                    $locked->id,
                    'Trip payment received via ToyyibPay',
                );
            } else {
                Log::error('ToyyibPay payment finalized but no driver to credit', ['gateway_transaction_id' => $locked->id]);
            }

            return $locked;
        });
    }

    /**
     * FPX Standard/personal-banking (RM1 flat) and DuitNow QR (1% or RM1,
     * whichever higher) cost the same in practice for any fare under RM100 —
     * this one formula covers both without needing to know in advance which
     * channel the passenger will pick.
     */
    private function computeFee(float $amountDue): float
    {
        $flat = (float) (SystemSetting::get('gateway_fee_flat_amount') ?? '1.00');

        return round(max($flat, $amountDue * 0.01), 2);
    }

    private function environment(): string
    {
        return str_contains((string) config('services.toyyibpay.base_url'), 'dev.') ? 'sandbox' : 'production';
    }

    private function normalizeStatus(mixed $raw): string
    {
        return match ((string) $raw) {
            '1' => 'success',
            '2' => 'pending',
            '3' => 'failed',
            default => 'unknown',
        };
    }

    private function ensurePaymentWindowOpen(TripPayment $payment): void
    {
        if (! $payment->trip || ! $payment->trip->trip_datetime) {
            return;
        }

        if ($payment->trip->trip_datetime->isFuture()) {
            throw ValidationException::withMessages([
                'payment' => 'Payment can only be processed after the trip time.',
            ]);
        }
    }
}
