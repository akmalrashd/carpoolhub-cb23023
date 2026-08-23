<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserNotification;
use App\Services\PaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Scheduled monthly (see bootstrap/app.php) for the 3rd of each month — a
 * few days' buffer past the 1st so payments confirmed right at the month
 * boundary have settled, rather than catching a driver/passenger mid-confirm
 * and reporting a balance that was already cleared.
 *
 * One notification per direction per user: what they still owe (as a rider)
 * and, separately, what's still owed to them (as a driver) — a user can be
 * both and gets both. Each breaks down by month and by counterparty so
 * someone who deliberately settles in a lump sum (rather than per trip) has
 * a running reference of exactly who they still owe, not just a total.
 */
class SendMonthlyPaymentSummary extends Command
{
    protected $signature = 'notifications:monthly-payment-summary';

    protected $description = 'Send each user a monthly summary of outstanding trip payments, by counterparty and month';

    public function __construct(private readonly PaymentService $paymentService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $sent = 0;

        User::query()->where('is_active', true)->chunkById(50, function (Collection $users) use (&$sent): void {
            foreach ($users as $user) {
                $sent += (int) $this->notifyDirection($user, 'owed_by_me');
                $sent += (int) $this->notifyDirection($user, 'owed_to_me');
            }
        });

        $this->info("Sent {$sent} monthly payment summary notification(s).");

        return self::SUCCESS;
    }

    private function notifyDirection(User $user, string $direction): bool
    {
        $breakdown = $this->paymentService->summarizeOutstandingBreakdown($user, $direction);

        if ($breakdown['total_amount'] <= 0) {
            return false;
        }

        $counterpartyCount = collect($breakdown['months'])
            ->flatMap(fn (array $month) => $month['rows'])
            ->pluck('counterparty_id')
            ->unique()
            ->count();

        $total = number_format($breakdown['total_amount'], 2);
        $isOwedToMe = $direction === 'owed_to_me';

        $title = $isOwedToMe
            ? 'Monthly Summary: Outstanding to You'
            : 'Monthly Summary: You Have Outstanding Payments';

        $counterpartyLabel = $isOwedToMe ? 'passenger' : 'driver';
        $counterpartyLabel .= $counterpartyCount === 1 ? '' : 's';

        $message = sprintf(
            'RM%s %s across %d %s. Tap to see the full breakdown.',
            $total,
            $isOwedToMe ? 'owed to you' : 'you owe',
            $counterpartyCount,
            $counterpartyLabel
        );

        UserNotification::query()->create([
            'user_id' => $user->id,
            'type' => 'payment',
            'title' => $title,
            'message' => $message,
            'telegram_message' => $this->buildTelegramMessage($direction, $breakdown),
            'related_type' => 'outstanding_summary',
            'related_id' => null,
            'is_read' => false,
        ]);

        return true;
    }

    private function buildTelegramMessage(string $direction, array $breakdown): string
    {
        $isOwedToMe = $direction === 'owed_to_me';

        $lines = [$isOwedToMe ? '📅 <b>Monthly Summary — Owed to You</b>' : '📅 <b>Monthly Payment Summary</b>', ''];

        foreach ($breakdown['months'] as $index => $month) {
            $lines[] = ($index === 0 ? '<b>' . e($month['month_label']) . '</b>' : e($month['month_label'])) . ':';

            foreach ($month['rows'] as $row) {
                $lines[] = sprintf(
                    '💰 RM%s %s %s (%d trip%s)',
                    number_format($row['amount'], 2),
                    $isOwedToMe ? 'from' : 'to',
                    e($row['counterparty_name']),
                    $row['records'],
                    $row['records'] === 1 ? '' : 's'
                );
            }

            $lines[] = '';
        }

        $lines[] = '━━━━━━━━━━━━━━';
        $lines[] = sprintf('<b>Total outstanding: RM%s</b>', number_format($breakdown['total_amount'], 2));

        return implode("\n", $lines);
    }
}
