<?php

namespace App\Support;

use App\Models\Trip;
use Carbon\Carbon;

/**
 * WhatsApp-style date-separator label for a chronological list of
 * timestamps: "Today"/"Yesterday" for the last two calendar days, the
 * weekday name for the rest of the current week, and a full date beyond
 * that. Everything is worked out in Trip::TIMEZONE, since the app does not
 * support multiple time zones and assumes every viewer is in Malaysia.
 */
class ChatDateLabel
{
    public static function forDate(Carbon $date): string
    {
        $today = Carbon::now(Trip::TIMEZONE)->startOfDay();
        $target = $date->clone()->setTimezone(Trip::TIMEZONE)->startOfDay();
        // diffInDays() returns a signed float on Carbon 3 (absolute defaults
        // to false, unlike Carbon 2), so it is forced to absolute and cast to
        // an int. Without that, a negative value, and the fact that 0.0 === 0
        // is false, would both skip "Today" and fall through to the weekday
        // branch below.
        $daysAgo = (int) $today->diffInDays($target, absolute: true);

        return match (true) {
            $daysAgo === 0 => 'Today',
            $daysAgo === 1 => 'Yesterday',
            $daysAgo < 7 => $target->format('l'),
            default => $target->format('d M Y'),
        };
    }
}
