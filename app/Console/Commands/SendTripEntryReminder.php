<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Console\Command;

/**
 * Scheduled monthly (see bootstrap/app.php) for the 25th — about 9 days
 * before the monthly outstanding-payment summary (3rd of next month) fires,
 * so there's real time to add anything missed before that summary reads
 * whatever's in the system as final.
 *
 * Deliberately a blanket reminder to every driver, not targeted at drivers
 * with zero trips logged this month: someone who logged trips early in the
 * month and then forgot partway through would have a non-zero count and
 * never get flagged by that kind of check, so it would miss exactly the
 * case it's meant to catch. Only drivers get this — TripController's
 * ensureCanManage() restricts trip creation to role === 'driver' strictly
 * (not admin), so an admin has nothing to act on here.
 */
class SendTripEntryReminder extends Command
{
    protected $signature = 'notifications:trip-entry-reminder';

    protected $description = 'Remind every driver to log any trips they gave this month, before the payment summary treats the data as final';

    public function handle(): int
    {
        $sent = 0;

        User::query()
            ->where('is_active', true)
            ->where('role', 'driver')
            ->chunkById(50, function ($drivers) use (&$sent): void {
                foreach ($drivers as $driver) {
                    UserNotification::query()->create([
                        'user_id' => $driver->id,
                        'type' => 'trip',
                        'title' => "Don't Forget to Log This Month's Trips",
                        'message' => "If you've given any rides this month that aren't in CarpoolHub yet, add them now so your payment summary is accurate.",
                        'related_type' => 'trip_entry_reminder',
                        'related_id' => null,
                        'is_read' => false,
                    ]);
                    $sent++;
                }
            });

        $this->info("Sent {$sent} trip entry reminder(s).");

        return self::SUCCESS;
    }
}
