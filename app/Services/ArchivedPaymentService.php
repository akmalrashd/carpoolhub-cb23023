<?php

namespace App\Services;

use App\Models\ArchivedTripPayment;
use App\Models\User;
use App\Models\UserNotification;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ArchivedPaymentService
{
    private const REMINDER_COOLDOWN_HOURS = 24;

    public function markPaid(User $actor, ArchivedTripPayment $payment, array $data): ArchivedTripPayment
    {
        $payment->loadMissing('archivedTrip');

        if ($payment->user_id !== $actor->id) {
            throw ValidationException::withMessages([
                'payment' => 'You can only mark your own archived payment.',
            ]);
        }

        if ($payment->payment_status === 'paid') {
            throw ValidationException::withMessages([
                'payment' => 'Archived payment has already been confirmed as paid.',
            ]);
        }

        return DB::transaction(function () use ($payment, $data, $actor): ArchivedTripPayment {
            $isSelfDrivenPayment = (int) ($payment->archivedTrip?->driver_id ?? 0) === (int) $actor->id;

            $payment->update([
                'payment_status' => $isSelfDrivenPayment ? 'paid' : 'pending_confirmation',
                'marked_paid_at' => now(),
                'confirmed_by' => $isSelfDrivenPayment ? $actor->id : null,
                'confirmed_at' => $isSelfDrivenPayment ? now() : null,
                'payment_method' => $data['payment_method'] ?? null,
                'remarks' => $data['remarks'] ?? null,
            ]);

            if ($isSelfDrivenPayment) {
                UserNotification::query()->create([
                    'user_id' => $payment->user_id,
                    'type' => 'payment',
                    'title' => 'Archived Payment Marked as Paid',
                    'message' => "Your self-driven archived payment for trip #{$payment->archived_trip_id} has been marked as paid.",
                    'related_type' => 'archived_trip_payment',
                    'related_id' => $payment->id,
                    'is_read' => false,
                ]);

                return $payment->refresh();
            }

            if ($payment->archivedTrip?->driver_id) {
                UserNotification::query()->create([
                    'user_id' => $payment->archivedTrip->driver_id,
                    'type' => 'payment',
                    'title' => 'Archived Payment Marked by Passenger',
                    'message' => "{$actor->name} marked archived payment as paid for trip #{$payment->archived_trip_id}.",
                    'related_type' => 'archived_trip_payment',
                    'related_id' => $payment->id,
                    'is_read' => false,
                ]);
            }

            return $payment->refresh();
        });
    }

    public function confirmPaid(User $actor, ArchivedTripPayment $payment): ArchivedTripPayment
    {
        $payment->loadMissing('archivedTrip', 'user');

        $canConfirm = $actor->role === 'admin' || $payment->archivedTrip?->driver_id === $actor->id;
        if (! $canConfirm) {
            throw ValidationException::withMessages([
                'payment' => 'Only the trip driver or admin can confirm this archived payment.',
            ]);
        }

        if ($payment->payment_status === 'paid') {
            throw ValidationException::withMessages([
                'payment' => 'Archived payment has already been confirmed as paid.',
            ]);
        }

        return DB::transaction(function () use ($payment, $actor): ArchivedTripPayment {
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
                'title' => $isDirectMark ? 'Archived Payment Marked by Driver' : 'Archived Payment Confirmed',
                'message' => $isDirectMark
                    ? "Driver marked your archived payment as paid for trip #{$payment->archived_trip_id}."
                    : "Your archived payment for trip #{$payment->archived_trip_id} has been confirmed.",
                'related_type' => 'archived_trip_payment',
                'related_id' => $payment->id,
                'is_read' => false,
            ]);

            return $payment->refresh();
        });
    }

    public function rejectPaidRequest(User $actor, ArchivedTripPayment $payment, string $reason): ArchivedTripPayment
    {
        $payment->loadMissing('archivedTrip', 'user');

        $canReview = $actor->role === 'admin' || $payment->archivedTrip?->driver_id === $actor->id;
        if (! $canReview) {
            throw ValidationException::withMessages([
                'payment' => 'Only the trip driver or admin can reject this archived payment request.',
            ]);
        }

        if ($payment->payment_status !== 'pending_confirmation') {
            throw ValidationException::withMessages([
                'payment' => 'Only pending confirmation archived payments can be rejected.',
            ]);
        }

        return DB::transaction(function () use ($payment, $reason): ArchivedTripPayment {
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
                'title' => 'Archived Payment Request Rejected',
                'message' => "Your archived payment for trip #{$payment->archived_trip_id} was rejected. Reason: {$reason}.",
                'related_type' => 'archived_trip_payment',
                'related_id' => $payment->id,
                'is_read' => false,
            ]);

            return $payment->refresh();
        });
    }

    public function sendReminder(User $actor, ArchivedTripPayment $payment): void
    {
        $payment->loadMissing('archivedTrip');

        $canNotify = $actor->role === 'admin' || $payment->archivedTrip?->driver_id === $actor->id;
        if (! $canNotify) {
            throw ValidationException::withMessages([
                'payment' => 'Only the trip driver or admin can send archived payment notifications.',
            ]);
        }

        if ($payment->payment_status === 'paid') {
            throw ValidationException::withMessages([
                'payment' => 'Notification is not needed for paid archived records.',
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
            'title' => 'Archived Payment Reminder',
            'message' => "Reminder for archived trip #{$payment->archived_trip_id}: please submit or update your payment details.",
            'related_type' => 'archived_trip_payment',
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
            ->where('title', 'Archived Payment Reminder')
            ->where('related_type', 'archived_trip_payment')
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
            ->where('title', 'Archived Payment Reminder')
            ->where('related_type', 'archived_trip_payment')
            ->where('related_id', $paymentId)
            ->max('created_at');

        if (! $lastSentAt) {
            return ['seconds_left' => 0];
        }

        $nextAt = Carbon::parse($lastSentAt)->addHours(self::REMINDER_COOLDOWN_HOURS);

        return ['seconds_left' => max(0, now()->diffInSeconds($nextAt, false))];
    }
}
