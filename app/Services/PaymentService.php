<?php

namespace App\Services;

use App\Models\TripPayment;
use App\Models\User;
use App\Models\UserNotification;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    private const REMINDER_COOLDOWN_HOURS = 24;

    public function paginateForUser(User $user, int $perPage = 12, ?array $tripIds = null): LengthAwarePaginator
    {
        return TripPayment::query()
            ->with(['trip.savedRoute', 'trip.driver', 'trip.participants.user', 'trip.parentTrip', 'trip.returnTrip', 'user'])
            ->where('user_id', $user->id)
            ->whereHas('trip', fn ($tripQuery) => $this->applyPayableTripScope($tripQuery))
            ->when(! empty($tripIds), fn ($query) => $query->whereIn('trip_id', $tripIds))
            ->latest('id')
            ->paginate($perPage, ['*'], 'mine_page');
    }

    public function paginateForDriver(User $user, int $perPage = 12, ?array $tripIds = null): LengthAwarePaginator
    {
        return TripPayment::query()
            ->with(['trip.savedRoute', 'trip.driver', 'trip.participants.user', 'trip.parentTrip', 'trip.returnTrip', 'user'])
            ->when(
                $user->role === 'admin',
                fn ($query) => $query->whereHas('trip', fn ($tripQuery) => $this->applyPayableTripScope($tripQuery)),
                fn ($query) => $query->whereHas('trip', fn ($tripQuery) => $tripQuery->where('driver_id', $user->id))
                    ->where('user_id', '!=', $user->id)
            )
            ->whereHas('trip', fn ($tripQuery) => $this->applyPayableTripScope($tripQuery))
            ->when(! empty($tripIds), fn ($query) => $query->whereIn('trip_id', $tripIds))
            ->latest('id')
            ->paginate($perPage, ['*'], 'driver_page');
    }

    public function summarizeForUser(User $user, ?array $tripIds = null): array
    {
        $myBaseQuery = TripPayment::query()
            ->where('user_id', $user->id)
            ->whereHas('trip', fn ($tripQuery) => $this->applyPayableTripScope($tripQuery))
            ->when(! empty($tripIds), fn ($query) => $query->whereIn('trip_id', $tripIds));

        $driverBaseQuery = TripPayment::query()
            ->when(
                $user->role === 'admin',
                fn ($query) => $query->whereHas('trip', fn ($tripQuery) => $this->applyPayableTripScope($tripQuery)),
                fn ($query) => $query->whereHas('trip', fn ($tripQuery) => $tripQuery->where('driver_id', $user->id))
                    ->where('user_id', '!=', $user->id)
            )
            ->whereHas('trip', fn ($tripQuery) => $this->applyPayableTripScope($tripQuery))
            ->when(! empty($tripIds), fn ($query) => $query->whereIn('trip_id', $tripIds));

        return [
            'my' => $this->summarizeByStatus($myBaseQuery),
            'driver' => $this->summarizeByStatus($driverBaseQuery),
        ];
    }

    public function summarizeOutstandingByPassenger(User $user, ?array $tripIds = null): array
    {
        $rows = TripPayment::query()
            ->select([
                'users.id as passenger_id',
                'users.name as passenger_name',
                DB::raw('COUNT(trip_payments.id) as records'),
                DB::raw("SUM(CASE WHEN trip_payments.payment_status = 'unpaid' THEN trip_payments.amount_due ELSE 0 END) as unpaid_amount"),
                DB::raw("SUM(CASE WHEN trip_payments.payment_status = 'pending_confirmation' THEN trip_payments.amount_due ELSE 0 END) as pending_amount"),
                DB::raw('SUM(trip_payments.amount_due) as total_amount'),
            ])
            ->join('users', 'users.id', '=', 'trip_payments.user_id')
            ->whereIn('trip_payments.payment_status', ['unpaid', 'pending_confirmation'])
            ->whereHas('trip', fn ($tripQuery) => $this->applyPayableTripScope($tripQuery))
            ->when(
                $user->role === 'admin',
                fn ($query) => $query->whereHas('trip', fn ($tripQuery) => $this->applyPayableTripScope($tripQuery)),
                fn ($query) => $query->whereHas('trip', fn ($tripQuery) => $tripQuery->where('driver_id', $user->id))
                    ->where('trip_payments.user_id', '!=', $user->id)
            )
            ->when(! empty($tripIds), fn ($query) => $query->whereIn('trip_payments.trip_id', $tripIds))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_amount')
            ->get();

        return [
            'total_amount' => (float) $rows->sum(fn ($row) => (float) $row->total_amount),
            'total_records' => (int) $rows->sum(fn ($row) => (int) $row->records),
            'passenger_count' => (int) $rows->count(),
            'rows' => $rows->map(fn ($row) => [
                'passenger_id' => (int) $row->passenger_id,
                'passenger_name' => (string) $row->passenger_name,
                'records' => (int) $row->records,
                'unpaid_amount' => (float) $row->unpaid_amount,
                'pending_amount' => (float) $row->pending_amount,
                'total_amount' => (float) $row->total_amount,
            ])->all(),
        ];
    }

    public function markPaid(User $actor, TripPayment $payment, array $data): TripPayment
    {
        $payment->loadMissing('trip');

        if ($payment->trip && $payment->trip->status === 'draft') {
            throw ValidationException::withMessages([
                'payment' => 'Draft trips do not require payment yet.',
            ]);
        }
        $this->ensureTripPaymentWindowOpen($payment);

        if ($payment->user_id !== $actor->id) {
            throw ValidationException::withMessages([
                'payment' => 'You can only mark your own payment.',
            ]);
        }

        if ($payment->payment_status === 'paid') {
            throw ValidationException::withMessages([
                'payment' => 'Payment has already been confirmed as paid.',
            ]);
        }

        return DB::transaction(function () use ($payment, $data, $actor): TripPayment {
            $isSelfDrivenPayment = (int) ($payment->trip?->driver_id ?? 0) === (int) $actor->id;

            $payment->update([
                'payment_status' => $isSelfDrivenPayment ? 'paid' : 'pending_confirmation',
                'marked_paid_at' => now(),
                'confirmed_by' => $isSelfDrivenPayment ? $actor->id : null,
                'confirmed_at' => $isSelfDrivenPayment ? now() : null,
                'payment_method' => $data['payment_method'] ?? null,
                'remarks' => $data['remarks'] ?? null,
            ]);

            $payment->loadMissing('trip');

            if ($isSelfDrivenPayment) {
                UserNotification::query()->create([
                    'user_id' => $payment->user_id,
                    'type' => 'payment',
                    'title' => 'Payment Marked as Paid',
                    'message' => "Your self-driven payment for trip #{$payment->trip_id} has been marked as paid.",
                    'related_type' => 'trip_payment',
                    'related_id' => $payment->id,
                    'is_read' => false,
                ]);

                return $payment->refresh();
            }

            UserNotification::query()->create([
                'user_id' => $payment->trip->driver_id,
                'type' => 'payment',
                'title' => 'Payment Marked by Passenger',
                'message' => "{$actor->name} marked payment as paid for trip #{$payment->trip_id}.",
                'related_type' => 'trip_payment',
                'related_id' => $payment->id,
                'is_read' => false,
            ]);

            return $payment->refresh();
        });
    }

    public function confirmPaid(User $actor, TripPayment $payment): TripPayment
    {
        $payment->loadMissing('trip', 'user');

        if ($payment->trip && $payment->trip->status === 'draft') {
            throw ValidationException::withMessages([
                'payment' => 'Draft trips do not require payment yet.',
            ]);
        }
        $this->ensureTripPaymentWindowOpen($payment);

        $canConfirm = $actor->role === 'admin' || $payment->trip->driver_id === $actor->id;
        if (! $canConfirm) {
            throw ValidationException::withMessages([
                'payment' => 'Only the trip driver or admin can confirm this payment.',
            ]);
        }

        if ($payment->payment_status === 'paid') {
            throw ValidationException::withMessages([
                'payment' => 'Payment has already been confirmed as paid.',
            ]);
        }

        return DB::transaction(function () use ($payment, $actor): TripPayment {
            $isDirectMark = $payment->payment_status === 'unpaid';

            $payment->update([
                'payment_status' => 'paid',
                'confirmed_by' => $actor->id,
                'confirmed_at' => now(),
                'marked_paid_at' => $payment->marked_paid_at ?? now(),
            ]);

            UserNotification::query()->create([
                'user_id' => $payment->user_id,
                'type' => 'payment',
                'title' => $isDirectMark ? 'Payment Marked by Driver' : 'Payment Confirmed',
                'message' => $isDirectMark
                    ? "Driver marked your payment as paid for trip #{$payment->trip_id}."
                    : "Your payment for trip #{$payment->trip_id} has been confirmed.",
                'related_type' => 'trip_payment',
                'related_id' => $payment->id,
                'is_read' => false,
            ]);

            return $payment->refresh();
        });
    }

    public function rejectPaidRequest(User $actor, TripPayment $payment, string $reason): TripPayment
    {
        $payment->loadMissing('trip', 'user');

        if ($payment->trip && $payment->trip->status === 'draft') {
            throw ValidationException::withMessages([
                'payment' => 'Draft trips do not require payment yet.',
            ]);
        }
        $this->ensureTripPaymentWindowOpen($payment);

        $canReview = $actor->role === 'admin' || $payment->trip->driver_id === $actor->id;
        if (! $canReview) {
            throw ValidationException::withMessages([
                'payment' => 'Only the trip driver or admin can reject this payment request.',
            ]);
        }

        if ($payment->payment_status !== 'pending_confirmation') {
            throw ValidationException::withMessages([
                'payment' => 'Only pending confirmation payments can be rejected.',
            ]);
        }

        return DB::transaction(function () use ($payment, $actor, $reason): TripPayment {
            $payment->update([
                'payment_status' => 'unpaid',
                'marked_paid_at' => null,
                'confirmed_by' => null,
                'confirmed_at' => null,
                'payment_method' => null,
                'remarks' => null,
            ]);

            UserNotification::query()->create([
                'user_id' => $payment->user_id,
                'type' => 'payment',
                'title' => 'Payment Request Rejected',
                'message' => "Your payment for trip #{$payment->trip_id} was rejected. Reason: {$reason}. Please submit again with updated details.",
                'related_type' => 'trip_payment',
                'related_id' => $payment->id,
                'is_read' => false,
            ]);

            return $payment->refresh();
        });
    }

    public function sendReminder(User $actor, TripPayment $payment): void
    {
        $payment->loadMissing('trip', 'user');

        if ($payment->trip && $payment->trip->status === 'draft') {
            throw ValidationException::withMessages([
                'payment' => 'Draft trips do not require payment notifications.',
            ]);
        }
        $this->ensureTripPaymentWindowOpen($payment);

        $canNotify = $actor->role === 'admin' || $payment->trip->driver_id === $actor->id;
        if (! $canNotify) {
            throw ValidationException::withMessages([
                'payment' => 'Only the trip driver or admin can send notifications.',
            ]);
        }

        if ($payment->payment_status === 'paid') {
            throw ValidationException::withMessages([
                'payment' => 'Notification is not needed for paid records.',
            ]);
        }

        $cooldown = $this->reminderCooldownForPayment($payment->id);
        if ($cooldown['seconds_left'] > 0) {
            throw ValidationException::withMessages([
                'payment' => 'Notification can only be sent once every 24 hours.',
            ]);
        }

        UserNotification::query()->create([
            'user_id' => $payment->user_id,
            'type' => 'payment',
            'title' => 'Payment Reminder',
            'message' => "Reminder for trip #{$payment->trip_id}: please submit or update your payment details.",
            'related_type' => 'trip_payment',
            'related_id' => $payment->id,
            'is_read' => false,
        ]);
    }

    public function reminderStateForPayments(Collection $payments): array
    {
        $paymentIds = $payments->pluck('id')->filter()->map(fn ($id) => (int) $id)->values();
        if ($paymentIds->isEmpty()) {
            return [];
        }

        $latestByPayment = UserNotification::query()
            ->select('related_id', DB::raw('MAX(created_at) as last_sent_at'))
            ->where('type', 'payment')
            ->where('title', 'Payment Reminder')
            ->where('related_type', 'trip_payment')
            ->whereIn('related_id', $paymentIds)
            ->groupBy('related_id')
            ->get()
            ->keyBy(fn ($row) => (int) $row->related_id);

        $result = [];
        foreach ($paymentIds as $paymentId) {
            $row = $latestByPayment->get($paymentId);
            if (! $row || empty($row->last_sent_at)) {
                $result[$paymentId] = [
                    'can_send' => true,
                    'seconds_left' => 0,
                    'next_at' => null,
                ];
                continue;
            }

            $nextAt = Carbon::parse($row->last_sent_at)->addHours(self::REMINDER_COOLDOWN_HOURS);
            $secondsLeft = max(0, now()->diffInSeconds($nextAt, false));

            $result[$paymentId] = [
                'can_send' => $secondsLeft === 0,
                'seconds_left' => $secondsLeft,
                'next_at' => $nextAt,
            ];
        }

        return $result;
    }

    private function reminderCooldownForPayment(int $paymentId): array
    {
        $lastSentAt = UserNotification::query()
            ->where('type', 'payment')
            ->where('title', 'Payment Reminder')
            ->where('related_type', 'trip_payment')
            ->where('related_id', $paymentId)
            ->max('created_at');

        if (! $lastSentAt) {
            return ['seconds_left' => 0];
        }

        $nextAt = Carbon::parse($lastSentAt)->addHours(self::REMINDER_COOLDOWN_HOURS);
        return ['seconds_left' => max(0, now()->diffInSeconds($nextAt, false))];
    }

    private function summarizeByStatus(Builder $baseQuery): array
    {
        $statuses = ['unpaid', 'pending_confirmation', 'paid'];
        $summary = [];

        foreach ($statuses as $status) {
            $statusQuery = (clone $baseQuery)->where('payment_status', $status);
            $summary[$status] = [
                'count' => (int) (clone $statusQuery)->count(),
                'amount' => (float) (clone $statusQuery)->sum('amount_due'),
            ];
        }

        $summary['total'] = [
            'count' => (int) (clone $baseQuery)->count(),
            'amount' => (float) (clone $baseQuery)->sum('amount_due'),
        ];

        return $summary;
    }

    private function applyPayableTripScope($tripQuery): void
    {
        $tripQuery
            ->activeOperational()
            ->where('status', '!=', 'draft')
            ->whereNotNull('trip_datetime');
    }

    private function ensureTripPaymentWindowOpen(TripPayment $payment): void
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
