<?php

namespace App\Services\Concerns;

use App\Models\Trip;

/**
 * Shared by PaymentService, TripService and TripJoinRequestService, which each
 * carried an identical copy of this pair — a wording change in one would
 * silently drift from the other two.
 */
trait FormatsTripLabel
{
    private function shortenAddress(string $address): string
    {
        $first = trim(explode(',', $address)[0]);
        $source = mb_strlen($first) >= 4 ? $first : $address;
        return mb_strimwidth($source, 0, 28, '…');
    }

    private function formatTripLabel(Trip $trip): string
    {
        if ($trip->pickup_name && $trip->destination_name) {
            $pickup      = $this->shortenAddress($trip->pickup_name);
            $destination = $this->shortenAddress($trip->destination_name);
            $date        = $trip->trip_datetime?->format('d M Y') ?? '';
            return $date ? "{$pickup} → {$destination} on {$date}" : "{$pickup} → {$destination}";
        }

        return 'Trip #' . ($trip->trip_ref ?? $trip->id);
    }
}
