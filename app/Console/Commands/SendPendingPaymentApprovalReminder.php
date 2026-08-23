<?php

namespace App\Console\Commands;

use App\Models\TripPayment;
use App\Models\UserNotification;
use App\Services\Concerns\FormatsTripLabel;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Scheduled daily (see bootstrap/app.php). A passenger marking a payment
 * paid moves it to pending_confirmation, but nothing pushed the driver to
 * actually confirm or reject it — it could sit there indefinitely. That
 * delay isn't harmless: PassengerReliabilityService counts an unconfirmed
 * payment against the passenger's score too, so a driver who never checks
 * quietly drags down someone who already paid.
 *
 * The trigger delay (driver_review_grace_days) is the same config value
 * PassengerReliabilityService freezes its overdue clock at, so "the driver
 * had a fair chance to act" means the same number of days in both places.
 * One digest per driver, not one notification per payment — a driver with
 * several passengers shouldn't get spammed. Repeats every REPEAT_INTERVAL_DAYS
 * if still ignored, so it can't be silently missed once and forgotten.
 */
class SendPendingPaymentApprovalReminder extends Command
{
    use FormatsTripLabel;

    private const REPEAT_INTERVAL_DAYS = 3;

    private const REMINDER_RELATED_TYPE = 'pending_payment_approval_reminder';

    protected $signature = 'notifications:pending-payment-reminder';

    protected $description = 'Remind drivers, as one digest each, about passenger payments still awaiting their confirmation';

    public function handle(): int
    {
        $graceDays = (int) config('passenger_reliability.driver_review_grace_days', 3);
        $cutoff = now()->subDays($graceDays);

        $payments = TripPayment::query()
            ->pendingConfirmation()
            ->whereNotNull('marked_paid_at')
            ->where('marked_paid_at', '<=', $cutoff)
            ->with(['trip', 'user'])
            ->get()
            ->filter(fn (TripPayment $payment) => $payment->trip && $payment->trip->driver_id);

        $remindedDrivers = 0;

        foreach ($payments->groupBy(fn (TripPayment $payment) => $payment->trip->driver_id) as $driverId => $driverPayments) {
            $driverId = (int) $driverId;

            if ($this->recentlyReminded($driverId)) {
                continue;
            }

            $this->notifyDriver($driverId, $driverPayments);
            $remindedDrivers++;
        }

        $this->info("Reminded {$remindedDrivers} driver(s) about payments awaiting approval.");

        return self::SUCCESS;
    }

    private function recentlyReminded(int $driverId): bool
    {
        return UserNotification::query()
            ->where('user_id', $driverId)
            ->where('related_type', self::REMINDER_RELATED_TYPE)
            ->where('created_at', '>=', now()->subDays(self::REPEAT_INTERVAL_DAYS))
            ->exists();
    }

    private function notifyDriver(int $driverId, Collection $payments): void
    {
        $count = $payments->count();
        $total = (float) $payments->sum('amount_due');

        $title = $count === 1
            ? 'A Payment Is Awaiting Your Approval'
            : "{$count} Payments Are Awaiting Your Approval";

        $message = sprintf(
            '%d passenger payment%s totaling RM%s %s still waiting for you to confirm or reject. Tap to review.',
            $count,
            $count === 1 ? '' : 's',
            number_format($total, 2),
            $count === 1 ? 'is' : 'are'
        );

        UserNotification::query()->create([
            'user_id' => $driverId,
            'type' => 'payment',
            'title' => $title,
            'message' => $message,
            'telegram_message' => $this->buildTelegramMessage($payments),
            'related_type' => self::REMINDER_RELATED_TYPE,
            'related_id' => null,
            'is_read' => false,
        ]);
    }

    private function buildTelegramMessage(Collection $payments): string
    {
        $lines = ['⏳ <b>Payments Awaiting Your Approval</b>', ''];

        foreach ($payments as $payment) {
            $daysWaiting = $payment->marked_paid_at ? (int) $payment->marked_paid_at->diffInDays(now()) : 0;

            $lines[] = sprintf(
                '💰 RM%s from %s — %s (%d day%s waiting)',
                number_format((float) $payment->amount_due, 2),
                e($payment->user->name ?? 'Passenger'),
                e($this->formatTripLabel($payment->trip)),
                $daysWaiting,
                $daysWaiting === 1 ? '' : 's'
            );
        }

        $lines[] = '';
        $lines[] = 'Confirm or reject each one in the app.';

        return implode("\n", $lines);
    }
}
