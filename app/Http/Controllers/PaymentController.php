<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payment\ConfirmPaidRequest;
use App\Http\Requests\Payment\MarkPaidRequest;
use App\Http\Requests\Payment\RejectPaidRequest;
use App\Http\Requests\Payment\SendReminderRequest;
use App\Models\TripPayment;
use App\Services\ArchiveService;
use App\Services\ArchivedPaymentService;
use App\Services\PaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly ArchiveService $archiveService,
        private readonly ArchivedPaymentService $archivedPaymentService
    )
    {
    }

    public function index(Request $request): View
    {
        $role = (string) $request->user()->role;
        $showMyPayments = $role !== 'admin';
        $canReviewQueue = in_array($role, ['admin', 'driver'], true);

        // Keep all payments visible on index.
        // Any trip_id/trip_ids query param is used only for client-side scroll/highlight.
        $tripIds = null;

        $myPayments = $this->paymentService->paginateForUser($request->user(), 12, $tripIds);
        $driverPayments = $canReviewQueue
            ? $this->paymentService->paginateForDriver($request->user(), 12, $tripIds)
            : null;
        $archivedDriverPayments = $canReviewQueue
            ? $this->archiveService->paginateArchivedPaymentsForDriver(
                $request->user(),
                null,
                12,
                'archived_driver_page',
                ['unpaid', 'pending_confirmation']
            )
            : null;
        $reminderState = $canReviewQueue && $driverPayments
            ? $this->paymentService->reminderStateForPayments($driverPayments->getCollection())
            : [];
        $archivedReminderState = $canReviewQueue && $archivedDriverPayments
            ? $this->archivedPaymentService->reminderStateForPayments($archivedDriverPayments->getCollection())
            : [];
        $summary = $this->paymentService->summarizeForUser($request->user(), $tripIds);
        $archivedSummary = $this->archiveService->summarizeArchivedPayments($request->user(), null);
        $passengerDebtSummary = $canReviewQueue
            ? $this->paymentService->summarizeOutstandingByPassenger($request->user(), $tripIds)
            : null;

        return view(
            'payments.index',
            compact(
                'myPayments',
                'driverPayments',
                'archivedDriverPayments',
                'summary',
                'archivedSummary',
                'passengerDebtSummary',
                'reminderState',
                'archivedReminderState',
                'showMyPayments',
                'canReviewQueue'
            )
        );
    }

    public function markPaid(MarkPaidRequest $request, TripPayment $payment): RedirectResponse
    {
        try {
            $this->paymentService->markPaid($request->user(), $payment, $request->validated());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()
            ->route('payments.index')
            ->with('status', 'Payment marked as paid. Waiting for driver confirmation.');
    }

    public function confirmPaid(ConfirmPaidRequest $request, TripPayment $payment): RedirectResponse
    {
        try {
            $this->paymentService->confirmPaid($request->user(), $payment);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()
            ->route('payments.index')
            ->with('status', 'Payment confirmed as paid.');
    }

    public function rejectPaid(RejectPaidRequest $request, TripPayment $payment): RedirectResponse
    {
        try {
            $this->paymentService->rejectPaidRequest($request->user(), $payment, $request->validated('rejection_reason'));
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()
            ->route('payments.index')
            ->with('status', 'Payment request rejected. Passenger has been notified to resubmit.');
    }

    public function sendReminder(SendReminderRequest $request, TripPayment $payment): RedirectResponse
    {
        try {
            $this->paymentService->sendReminder($request->user(), $payment);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()
            ->route('payments.index')
            ->with('status', 'Passenger notified.');
    }
}
