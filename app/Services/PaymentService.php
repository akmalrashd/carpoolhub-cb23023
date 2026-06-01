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

    public function paginateForUser(User $user, int $perPage = 12, array $filters = [], ?array $tripIds = null): LengthAwarePaginator
    {
        $query = TripPayment::query()
            ->with(['trip.savedRoute', 'trip.driver', 'trip.participants.user', 'trip.passengerRoutePoints.user', 'trip.parentTrip', 'trip.returnTrip', 'user'])
            ->where('user_id', $user->id)
            ->whereHas('trip', fn ($tripQuery) => $this->applyPayableTripScope($tripQuery))
            ->when(! empty($tripIds), fn ($query) => $query->whereIn('trip_id', $tripIds));

        $this->applyIndexFilters($query, $filters);

        return $query
            ->latest('id')
            ->paginate($perPage, ['*'], 'mine_page');
    }

    public function paginateForDriver(User $user, int $perPage = 12, array $filters = [], ?array $tripIds = null): LengthAwarePaginator
    {
        $query = TripPayment::query()
            ->with(['trip.savedRoute', 'trip.driver', 'trip.participants.user', 'trip.passengerRoutePoints.user', 'trip.parentTrip', 'trip.returnTrip', 'user'])
            ->when(
                $user->role === 'admin',
                fn ($query) => $query->whereHas('trip', fn ($tripQuery) => $this->applyPayableTripScope($tripQuery)),
                fn ($query) => $query->whereHas('trip', fn ($tripQuery) => $tripQuery->where('driver_id', $user->id))
                    ->where('user_id', '!=', $user->id)
            )
            ->whereHas('trip', fn ($tripQuery) => $this->applyPayableTripScope($tripQuery))
            ->when(! empty($tripIds), fn ($query) => $query->whereIn('trip_id', $tripIds));

        $this->applyIndexFilters($query, $filters);

        return $query
            ->latest('id')
            ->paginate($perPage, ['*'], 'driver_page');
    }

    public function indexCountsForUser(User $user, array $filters = [], bool $includeDriverQueue = false, ?array $tripIds = null): array
    {
        if ($user->role === 'admin') {
            $allQuery = TripPayment::query()
                ->whereHas('trip', fn ($tripQuery) => $this->applyPayableTripScope($tripQuery))
                ->when(! empty($tripIds), fn ($query) => $query->whereIn('trip_id', $tripIds));

            $countFilters = $filters;
            unset($countFilters['payment_filter'], $countFilters['direction']);
            $this->applyIndexFilters($allQuery, $countFilters);

            return [
                'all' => (int) (clone $allQuery)->count(),
                'pay' => 0,
                'collect' => (int) (clone $allQuery)->count(),
                'unpaid' => (int) (clone $allQuery)->where('payment_status', 'unpaid')->count(),
                'review' => (int) (clone $allQuery)->where('payment_status', 'pending_confirmation')->count(),
                'confirmed' => (int) (clone $allQuery)->where('payment_status', 'paid')->count(),
            ];
        }

        $payQuery = TripPayment::query()
            ->where('user_id', $user->id)
            ->whereHas('trip', fn ($tripQuery) => $this->applyPayableTripScope($tripQuery))
            ->when(! empty($tripIds), fn ($query) => $query->whereIn('trip_id', $tripIds));

        $collectQuery = TripPayment::query()
            ->when(
                $user->role === 'admin',
                fn ($query) => $query->whereHas('trip', fn ($tripQuery) => $this->applyPayableTripScope($tripQuery)),
                fn ($query) => $query->whereHas('trip', fn ($tripQuery) => $tripQuery->where('driver_id', $user->id))
                    ->where('user_id', '!=', $user->id)
            )
            ->whereHas('trip', fn ($tripQuery) => $this->applyPayableTripScope($tripQuery))
            ->when(! empty($tripIds), fn ($query) => $query->whereIn('trip_id', $tripIds));

        $countFilters = $filters;
        unset($countFilters['payment_filter']);
        $this->applyIndexFilters($payQuery, $countFilters);
        $this->applyIndexFilters($collectQuery, $countFilters);

        $direction = (string) ($filters['direction'] ?? 'all');
        $statusQueries = collect();
        if ($direction !== 'collect') {
            $statusQueries->push(clone $payQuery);
        }
        if ($includeDriverQueue && $direction !== 'pay') {
            $statusQueries->push(clone $collectQuery);
        }

        $sumStatus = function (string $status) use ($statusQueries): int {
            return (int) $statusQueries->sum(fn ($query) => (clone $query)->where('payment_status', $status)->count());
        };

        $payCount = (int) (clone $payQuery)->count();
        $collectCount = $includeDriverQueue ? (int) (clone $collectQuery)->count() : 0;

        return [
            'all' => (int) $statusQueries->sum(fn ($query) => (clone $query)->count()),
            'pay' => $payCount,
            'collect' => $collectCount,
            'unpaid' => $sumStatus('unpaid'),
            'review' => $sumStatus('pending_confirmation'),
            'confirmed' => $sumStatus('paid'),
        ];
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
        $rows = (clone $baseQuery)
            ->selectRaw("
                payment_status,
                COUNT(*) as cnt,
                SUM(amount_due) as total
            ")
            ->groupBy('payment_status')
            ->get()
            ->keyBy('payment_status');

        $make = fn (string $status) => [
            'count' => (int) ($rows->get($status)?->cnt ?? 0),
            'amount' => (float) ($rows->get($status)?->total ?? 0),
        ];

        return [
            'unpaid' => $make('unpaid'),
            'pending_confirmation' => $make('pending_confirmation'),
            'paid' => $make('paid'),
            'total' => [
                'count' => (int) $rows->sum('cnt'),
                'amount' => (float) $rows->sum('total'),
            ],
        ];
    }

    private function applyIndexFilters(Builder $query, array $filters): void
    {
        $paymentFilter = (string) ($filters['payment_filter'] ?? 'all');
        if ($paymentFilter === 'review') {
            $query->where('payment_status', 'pending_confirmation');
        } elseif ($paymentFilter === 'confirmed') {
            $query->where('payment_status', 'paid');
        } elseif ($paymentFilter === 'unpaid') {
            $query->where('payment_status', 'unpaid');
        }

        if (! empty($filters['date_from'])) {
            $from = Carbon::parse((string) $filters['date_from'])->startOfDay();
            $query->whereHas('trip', fn ($tripQuery) => $tripQuery->where('trip_datetime', '>=', $from));
        }

        if (! empty($filters['date_to'])) {
            $to = Carbon::parse((string) $filters['date_to'])->endOfDay();
            $query->whereHas('trip', fn ($tripQuery) => $tripQuery->where('trip_datetime', '<=', $to));
        }

        if (! empty($filters['visibility'])) {
            $visibility = (string) $filters['visibility'];
            $query->whereHas('trip', fn ($tripQuery) => $tripQuery->where('visibility', $visibility));
        }

        if (! empty($filters['payment_search'])) {
            $search = trim((string) $filters['payment_search']);
            $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery
                    ->whereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('trip', function ($tripQuery) use ($search): void {
                        $tripQuery
                            ->where('pickup_name', 'like', "%{$search}%")
                            ->orWhere('destination_name', 'like', "%{$search}%")
                            ->orWhereHas('driver', fn ($driverQuery) => $driverQuery->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('savedRoute', fn ($routeQuery) => $routeQuery->where('route_name', 'like', "%{$search}%"));
                    });
            });
        }
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
