<?php

namespace App\Console\Commands;

use App\Models\TripPayment;
use App\Models\UserNotification;
use App\Services\Concerns\FormatsTripLabel;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Scheduled daily (see bootstrap/app.php). PassengerReliabilityService no
 * longer counts a trip as overdue until summary_day_of_month + days_after_
 * summary days after the trip's month ends (config/passenger_reliability.php)
 * — matching how real users actually pay, in one lump sum after seeing their
 * monthly summary, not per trip. This command is the one warning shot before
 * that grace period actually runs out: a single lump-sum "settle up before
 * this starts affecting your rating" nudge, not a recurring dun. Once the
 * deadline passes without payment, this stays quiet — the existing monthly
 * summary is what keeps reporting the balance after that point.
 *
 * Runs daily rather than once a month because the deadline itself falls on a
 * slightly different calendar date each month (it's derived from LAST_DAY,
 * so it tracks month length) — a fixed day-of-month schedule would drift.
 * The per-user cooldown below is what keeps a daily check from re-sending
 * the same warning on each of the days it's in range for.
 */
class SendPaymentGraceDeadlineWarning extends Command
{
    use FormatsTripLabel;

    /** How many days ahead of the deadline the single warning fires. */
    private const WARN_DAYS_BEFORE_DEADLINE = 3;

    /**
     * Longer than the warn window above, so a daily run can't re-send the
     * same warning more than once while a trip's deadline is in range.
     */
    private const COOLDOWN_DAYS = 6;

    private const WARNING_RELATED_TYPE = 'payment_grace_deadline_warning';

    protected $signature = 'notifications:payment-grace-reminder';

    protected $description = "Warn passengers, once per deadline, before an unpaid trip starts counting against their reliability score";

    public function handle(): int
    {
        $offsetDays = (int) config('passenger_reliability.overdue_grace.summary_day_of_month', 3)
            + (int) config('passenger_reliability.overdue_grace.days_after_summary', 14);

        $payments = TripPayment::query()
            ->where('payment_status', TripPayment::STATUS_UNPAID)
            ->join('trips', 'trips.id', '=', 'trip_payments.trip_id')
            ->whereNotIn('trips.status', ['draft', 'scheduled'])
            ->whereRaw(
                'DATE_ADD(LAST_DAY(trips.trip_datetime), INTERVAL ? DAY) BETWEEN ? AND ?',
                [$offsetDays, now(), now()->addDays(self::WARN_DAYS_BEFORE_DEADLINE)]
            )
            ->select('trip_payments.*')
            ->with(['trip', 'user'])
            ->get();

        $warnedPassengers = 0;

        foreach ($payments->groupBy('user_id') as $passengerId => $passengerPayments) {
            $passengerId = (int) $passengerId;

            if ($this->recentlyWarned($passengerId)) {
                continue;
            }

            $this->warnPassenger($passengerId, $passengerPayments, $offsetDays);
            $warnedPassengers++;
        }

        $this->info("Warned {$warnedPassengers} passenger(s) about an approaching payment deadline.");

        return self::SUCCESS;
    }

    private function recentlyWarned(int $passengerId): bool
    {
        return UserNotification::query()
            ->where('user_id', $passengerId)
            ->where('related_type', self::WARNING_RELATED_TYPE)
            ->where('created_at', '>=', now()->subDays(self::COOLDOWN_DAYS))
            ->exists();
    }

    private function warnPassenger(int $passengerId, Collection $payments, int $offsetDays): void
    {
        $count = $payments->count();
        $total = (float) $payments->sum('amount_due');

        $deadline = $payments
            ->map(fn (TripPayment $payment) => $payment->trip?->trip_datetime
                ? Carbon::instance($payment->trip->trip_datetime)->endOfMonth()->startOfDay()->addDays($offsetDays)
                : null)
            ->filter()
            ->max();

        $deadlineLabel = $deadline ? $deadline->format('d M Y') : 'soon';

        $title = $count === 1
            ? 'Settle Your Payment Before It Affects Your Rating'
            : "Settle {$count} Payments Before They Affect Your Rating";

        $message = sprintf(
            'RM%s across %d trip%s still unpaid. Settle by %s to keep it from counting against your reliability score.',
            number_format($total, 2),
            $count,
            $count === 1 ? '' : 's',
            $deadlineLabel
        );

        UserNotification::query()->create([
            'user_id' => $passengerId,
            'type' => 'payment',
            'title' => $title,
            'message' => $message,
            'telegram_message' => $this->buildTelegramMessage($payments, $deadlineLabel, $total),
            'related_type' => self::WARNING_RELATED_TYPE,
            'related_id' => null,
            'is_read' => false,
        ]);
    }

    private function buildTelegramMessage(Collection $payments, string $deadlineLabel, float $total): string
    {
        $lines = ['⏰ <b>Settle Up Before It Affects Your Rating</b>', ''];

        foreach ($payments as $payment) {
            $lines[] = sprintf(
                '💰 RM%s — %s',
                number_format((float) $payment->amount_due, 2),
                e($this->formatTripLabel($payment->trip))
            );
        }

        $lines[] = '';
        $lines[] = sprintf('<b>Total: RM%s</b> — settle by %s.', number_format($total, 2), e($deadlineLabel));

        return implode("\n", $lines);
    }
}
