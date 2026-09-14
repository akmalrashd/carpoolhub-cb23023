<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * How many days a passenger has to rate a completed public trip's
     * driver before the window closes for good (SendDriverRatingInviteReminder
     * stops inviting/reminding, and the trip drops out of
     * DriverRatingService::eligibleTripsToRate). 14 days, not shorter: a
     * single star-tap has no urgency the way a payment deadline does, and
     * it needs enough runway for the daily reminder + Telegram touchpoints
     * to reach someone who doesn't open the app every day — the in-chat
     * CTA alone is only live for the first ~3 days (chat_retention_days_after).
     */
    public function up(): void
    {
        $now = now();
        DB::table('system_settings')->insert([
            ['key' => 'driver_rating_window_days', 'value' => '14', 'updated_by' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        DB::table('system_settings')->where('key', 'driver_rating_window_days')->delete();
    }
};
