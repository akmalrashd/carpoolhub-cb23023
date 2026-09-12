<?php

namespace App\Http\Controllers;

use App\Models\TripPayment;
use App\Services\GatewayPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class GatewayPaymentController extends Controller
{
    public function __construct(
        private readonly GatewayPaymentService $gatewayPaymentService,
    ) {}

    public function pay(Request $request, TripPayment $payment): RedirectResponse
    {
        try {
            $url = $this->gatewayPaymentService->createTransactionAndRedirectUrl($request->user(), $payment);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()->away($url);
    }

    /**
     * Combines every selected payment (must all be owed to the same driver)
     * into one ToyyibPay bill — one gateway fee for the whole batch instead
     * of one per trip.
     */
    public function payBulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'payment_ids' => ['required', 'array', 'min:1', 'max:200'],
            'payment_ids.*' => ['integer', 'exists:trip_payments,id'],
        ]);

        try {
            $url = $this->gatewayPaymentService->createBulkTransactionAndRedirectUrl($request->user(), $validated['payment_ids']);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()->away($url);
    }

    /**
     * Browser redirect back from ToyyibPay's hosted checkout. Works on
     * localhost (unlike the Callback below) since it's just the user's own
     * browser navigating — but its query-string status is unsigned, so
     * GatewayPaymentService::handleReturn() ignores it and pulls the
     * authoritative status from ToyyibPay's API instead.
     */
    public function return(Request $request): RedirectResponse
    {
        $result = $this->gatewayPaymentService->handleReturn($request->query('billcode'));

        $redirect = redirect()->route('payments.index', array_filter([
            'gateway' => $result['success'] ? 'success' : 'failed',
            'trip_payment' => $result['is_bulk'] ? null : $result['trip_payment_id'],
            'gateway_bulk' => $result['is_bulk'] ? '1' : null,
        ]));

        return $result['success']
            ? $redirect->with('status', 'Payment successful — your wallet has been credited.')
            : $redirect->withErrors(['payment' => 'Payment was not completed. You can try again or pay by direct bank transfer.']);
    }

    /**
     * Called by ToyyibPay's servers, not the browser — no session, no CSRF
     * token, excluded from CSRF verification in bootstrap/app.php. The MD5
     * hash (verified inside handleCallback) is what stands in for that.
     * Cannot reach a localhost URL per ToyyibPay's own docs — the Return URL
     * above and the scheduled reconciliation job are what make this app
     * work correctly without it during local development.
     */
    public function callback(Request $request): Response
    {
        try {
            $this->gatewayPaymentService->handleCallback($request->all());
        } catch (Throwable $e) {
            Log::error('ToyyibPay callback route failed: '.$e->getMessage());
        }

        // ToyyibPay expects a fast 200 regardless of outcome, or it retry-storms.
        return response('OK', 200);
    }
}
