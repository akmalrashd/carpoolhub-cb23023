<?php

namespace App\Support;

use App\Models\Trip;
use Carbon\Carbon;

/**
 * WhatsApp-style date-separator label for a chronological list of
 * timestamps: "Today"/"Yesterday" for the last two calendar days, the
 * weekday name for the rest of the current week, and a full date beyond
 * that — always in Trip::TIMEZONE, since this app has no multi-timezone
 * support and every viewer is assumed to be in Malaysia.
 */
class ChatDateLabel
{
    public static function forDate(Carbon $date): string
    {
        $today = Carbon::now(Trip::TIMEZONE)->startOfDay();
        $target = $date->clone()->setTimezone(Trip::TIMEZONE)->startOfDay();
        // diffInDays() returns a signed float on Carbon 3 (absolute defaults
        // to false, unlike Carbon 2) — force absolute and cast to int, since
        // both a negative value and 0.0 === 0 being false would otherwise
        // fall through to the weekday-name branch below instead of "Today".
        $daysAgo = (int) $today->diffInDays($target, absolute: true);

        return match (true) {
            $daysAgo === 0 => 'Today',
            $daysAgo === 1 => 'Yesterday',
            $daysAgo < 7 => $target->format('l'),
            default => $target->format('d M Y'),
        };
    }
}
