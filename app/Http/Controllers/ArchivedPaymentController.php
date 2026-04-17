<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payment\ConfirmPaidRequest;
use App\Http\Requests\Payment\MarkPaidRequest;
use App\Http\Requests\Payment\RejectPaidRequest;
use App\Http\Requests\Payment\SendReminderRequest;
use App\Models\ArchivedTripPayment;
use App\Services\ArchivedPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class ArchivedPaymentController extends Controller
{
    public function __construct(
        private readonly ArchivedPaymentService $archivedPaymentService
    )
    {
    }

    public function markPaid(MarkPaidRequest $request, ArchivedTripPayment $payment): RedirectResponse
    {
        try {
            $this->archivedPaymentService->markPaid($request->user(), $payment, $request->validated());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        return back()->with('status', 'Archived payment marked as paid. Waiting for driver confirmation.');
    }

    public function confirmPaid(ConfirmPaidRequest $request, ArchivedTripPayment $payment): RedirectResponse
    {
        try {
            $this->archivedPaymentService->confirmPaid($request->user(), $payment);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', 'Archived payment confirmed as paid.');
    }

    public function rejectPaid(RejectPaidRequest $request, ArchivedTripPayment $payment): RedirectResponse
    {
        try {
            $this->archivedPaymentService->rejectPaidRequest($request->user(), $payment, $request->validated('rejection_reason'));
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        return back()->with('status', 'Archived payment request rejected.');
    }

    public function sendReminder(SendReminderRequest $request, ArchivedTripPayment $payment): RedirectResponse
    {
        try {
            $this->archivedPaymentService->sendReminder($request->user(), $payment);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', 'Archived payment reminder sent.');
    }
}
