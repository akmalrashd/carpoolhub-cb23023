<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payment\ConfirmPaidRequest;
use App\Http\Requests\Payment\MarkPaidRequest;
use App\Http\Requests\Payment\RejectPaidRequest;
use App\Http\Requests\Payment\SendReminderRequest;
use App\Models\TripPayment;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'payment_filter' => ['nullable', 'in:all,unpaid,review,confirmed'],
            'direction' => ['nullable', 'in:all,pay,collect'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'visibility' => ['nullable', 'in:public,private'],
            'payment_search' => ['nullable', 'string', 'max:120'],
        ]);
        $filters['payment_filter'] = $filters['payment_filter'] ?? 'all';
        $filters['direction'] = $filters['direction'] ?? 'all';

        $dateFrom = ! empty($filters['date_from']) ? Carbon::parse($filters['date_from']) : null;
        $dateTo   = ! empty($filters['date_to'])   ? Carbon::parse($filters['date_to'])   : null;

        $summaryLabel = match (true) {
            $dateFrom !== null && $dateTo !== null => $dateFrom->format('d M') . ' – ' . $dateTo->format('d M Y'),
            $dateFrom !== null => 'From ' . $dateFrom->format('d M Y'),
            $dateTo   !== null => 'Until ' . $dateTo->format('d M Y'),
            default => strtoupper(now()->format('F Y')),
        };

        $role = (string) $request->user()->role;
        $isAdmin = $role === 'admin';
        
        if ($isAdmin) {
            $filters['direction'] = 'all';
        }

        $showMyPayments = ! $isAdmin;
        $canReviewQueue = in_array($role, ['admin', 'driver'], true);

        // Keep all payments visible on index.
        // Any trip_id/trip_ids query param is used only for client-side scroll/highlight.
        $tripIds = null;

        $showPayRecords = $showMyPayments && ($filters['direction'] ?? 'all') !== 'collect';
        $showCollectRecords = $canReviewQueue && ($filters['direction'] ?? 'all') !== 'pay';

        $myPayments = $showPayRecords
            ? $this->paymentService->paginateForUser($request->user(), 12, $filters, $tripIds)
            : null;
        $driverPayments = $showCollectRecords
            ? $this->paymentService->paginateForDriver($request->user(), 12, $filters, $tripIds)
            : null;
        $reminderState = $canReviewQueue && $driverPayments
            ? $this->paymentService->reminderStateForPayments($driverPayments->getCollection())
            : [];
        $summary = $this->paymentService->summarizeForUser(
            $request->user(),
            $tripIds,
            $filters['date_from'] ?? null,
            $filters['date_to'] ?? null,
        );

        $paymentCounts = $this->paymentService->indexCountsForUser($request->user(), $filters, $canReviewQueue, $tripIds);
        $passengerDebtSummary = $canReviewQueue
            ? $this->paymentService->summarizeOutstandingByPassenger($request->user(), $tripIds)
            : null;

        if (! $request->ajax()) {
            return view('payments.index', [
                'myPayments' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 12),
                'driverPayments' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 12),
                'summary' => $summary,
                'paymentCounts' => [],
                'passengerDebtSummary' => [],
                'reminderState' => [],
                'isAdmin' => $isAdmin,
                'canReviewQueue' => $canReviewQueue,
                'filters' => $filters,
                'summaryLabel' => $summaryLabel,
                'showMyPayments' => $showMyPayments,
                'initialLoad' => true,
            ]);
        }

        return view(
            'payments.index',
            compact(
                'myPayments',
                'driverPayments',
                'summary',
                'paymentCounts',
                'passengerDebtSummary',
                'reminderState',
                'showMyPayments',
                'canReviewQueue',
                'isAdmin',
                'filters',
                'summaryLabel'
            ) + ['initialLoad' => false]
        );
    }

    public function markPaid(MarkPaidRequest $request, TripPayment $payment): RedirectResponse|JsonResponse
    {
        try {
            $payment = $this->paymentService->markPaid($request->user(), $payment, $request->validated());
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => collect($exception->errors())->flatten()->first() ?: 'Payment could not be updated.',
                    'errors' => $exception->errors(),
                ], 422);
            }

            return back()->withErrors($exception->errors());
        }

        $message = $payment->payment_status === TripPayment::STATUS_PAID
            ? 'Payment marked as paid.'
            : 'Payment marked as paid. Waiting for driver confirmation.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'payment_status' => $payment->payment_status,
            ]);
        }

        return redirect()
            ->route('payments.index')
            ->with('status', $message);
    }

    public function confirmPaid(ConfirmPaidRequest $request, TripPayment $payment): RedirectResponse|JsonResponse
    {
        try {
            $this->paymentService->confirmPaid($request->user(), $payment);
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => collect($exception->errors())->flatten()->first() ?: 'Payment could not be confirmed.',
                    'errors' => $exception->errors(),
                ], 422);
            }

            return back()->withErrors($exception->errors());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Payment confirmed as paid.',
                'payment_status' => TripPayment::STATUS_PAID,
            ]);
        }

        return redirect()
            ->route('payments.index')
            ->with('status', 'Payment confirmed as paid.');
    }

    public function rejectPaid(RejectPaidRequest $request, TripPayment $payment): RedirectResponse|JsonResponse
    {
        try {
            $this->paymentService->rejectPaidRequest($request->user(), $payment, $request->validated('rejection_reason'));
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => collect($exception->errors())->flatten()->first() ?: 'Payment request could not be rejected.',
                    'errors' => $exception->errors(),
                ], 422);
            }

            return back()->withErrors($exception->errors());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Payment request rejected. Passenger has been notified to resubmit.',
                'payment_status' => TripPayment::STATUS_UNPAID,
            ]);
        }

        return redirect()
            ->route('payments.index')
            ->with('status', 'Payment request rejected. Passenger has been notified to resubmit.');
    }

    public function sendReminder(SendReminderRequest $request, TripPayment $payment): RedirectResponse|JsonResponse
    {
        try {
            $this->paymentService->sendReminder($request->user(), $payment);
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => collect($exception->errors())->flatten()->first() ?: 'Passenger could not be notified.',
                    'errors' => $exception->errors(),
                ], 422);
            }

            return back()->withErrors($exception->errors());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Passenger notified.',
            ]);
        }

        return redirect()
            ->route('payments.index')
            ->with('status', 'Passenger notified.');
    }
    public function bulkConfirm(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Capped: every id costs an `exists` query here and, once approved,
            // a transaction plus a notification plus a synchronous web-push
            // flush. Uncapped, one request could be made to do unbounded work.
            // 200 is far above the real page size (12 rows paginated).
            'payment_ids' => ['required', 'array', 'min:1', 'max:200'],
            'payment_ids.*' => ['integer', 'exists:trip_payments,id'],
        ]);

        $user = $request->user();
        $payments = TripPayment::whereIn('id', $validated['payment_ids'])->get();

        $count = 0;
        foreach ($payments as $payment) {
            try {
                $this->paymentService->confirmPaid($user, $payment);
                $count++;
            } catch (\Exception $e) {
                // Ignore individual errors during bulk approve
            }
        }

        return redirect()
            ->route('payments.index')
            ->with('status', "$count payment(s) marked as paid.");
    }

    public function approveAllPending(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        // trip_payments has no `status` column and no `pending_review` value —
        // the column is `payment_status` enum('unpaid','pending_confirmation',
        // 'paid'). The old filter threw "Unknown column 'status'" on every
        // click, so this endpoint had never once approved a payment.
        $pendingPayments = TripPayment::query()
            ->whereHas('trip', function ($q) use ($user) {
                $q->where('driver_id', $user->id);
            })
            ->where('payment_status', TripPayment::STATUS_PENDING_CONFIRMATION)
            ->get();

        $count = 0;
        foreach ($pendingPayments as $payment) {
            try {
                $this->paymentService->confirmPaid($user, $payment);
                $count++;
            } catch (\Exception $e) {
                // Ignore individual errors during bulk approve
            }
        }

        if ($count > 0) {
            return redirect()
                ->route('payments.index')
                ->with('status', "$count payment(s) successfully approved.");
        }

        return redirect()
            ->route('payments.index')
            ->with('status', 'No pending payments found.');
    }
}
