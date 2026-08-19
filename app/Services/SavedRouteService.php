<?php

namespace App\Services;

use App\Models\SavedRoute;
use App\Models\Connection;
use App\Models\Trip;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SavedRouteService
{
    public function paginateForUser(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return SavedRoute::query()
            ->with('passengerStops.user')
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($perPage);
    }

    public function selectablePassengersFor(User $user): EloquentCollection
    {
        return User::query()
            ->whereIn('id', Connection::acceptedUserIdsFor($user))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function create(User $user, array $data): SavedRoute
    {
        $stops = $data['passenger_stops'] ?? [];
        unset($data['passenger_stops']);

        return DB::transaction(function () use ($user, $data, $stops): SavedRoute {
            $savedRoute = SavedRoute::query()->create([
                ...$data,
                'user_id' => $user->id,
                'share_code' => $this->generateShareCode(),
            ]);

            $this->syncPassengerStops($savedRoute, $user, $stops);

            return $savedRoute->refresh()->load('passengerStops.user');
        });
    }

    /**
     * Add someone else's saved route to $user's own list via its share code.
     * Copies the route's points/fare into a brand new row owned by $user (with
     * its own fresh share code) rather than granting access to the original —
     * a saved route's passenger stops are tied to the owner's own accepted
     * connections, so those are deliberately not copied; $user adds their own
     * from the edit screen if they want any.
     */
    public function redeemShareCode(User $user, string $code): SavedRoute
    {
        $code = trim($code);

        $source = SavedRoute::query()->where('share_code', $code)->first();
        if (! $source) {
            throw ValidationException::withMessages([
                'code' => 'No saved route matches that code. Check the code and try again.',
            ]);
        }

        if ((int) $source->user_id === (int) $user->id) {
            throw ValidationException::withMessages([
                'code' => 'That code belongs to one of your own saved routes.',
            ]);
        }

        return DB::transaction(function () use ($source, $user): SavedRoute {
            return SavedRoute::query()->create([
                'user_id' => $user->id,
                'share_code' => $this->generateShareCode(),
                'route_name' => $source->route_name,
                'point_a_name' => $source->point_a_name,
                'point_a_latitude' => $source->point_a_latitude,
                'point_a_longitude' => $source->point_a_longitude,
                'point_b_name' => $source->point_b_name,
                'point_b_latitude' => $source->point_b_latitude,
                'point_b_longitude' => $source->point_b_longitude,
                'default_fare' => $source->default_fare,
                'is_active' => true,
            ]);
        });
    }

    private function generateShareCode(): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (SavedRoute::query()->where('share_code', $code)->exists());

        return $code;
    }

    public function update(SavedRoute $savedRoute, array $data): SavedRoute
    {
        $stops = $data['passenger_stops'] ?? [];
        unset($data['passenger_stops']);

        return DB::transaction(function () use ($savedRoute, $data, $stops): SavedRoute {
            $savedRoute->update($data);
            $this->syncPassengerStops($savedRoute, $savedRoute->user, $stops);

            return $savedRoute->refresh()->load('passengerStops.user');
        });
    }

    public function toggleActive(SavedRoute $savedRoute): SavedRoute
    {
        $savedRoute->update([
            'is_active' => ! (bool) $savedRoute->is_active,
        ]);

        return $savedRoute->refresh();
    }

    public function delete(SavedRoute $savedRoute): void
    {
        DB::transaction(function () use ($savedRoute): void {
            $tripIds = Trip::query()
                ->where('saved_route_id', $savedRoute->id)
                ->pluck('id');

            if ($tripIds->isNotEmpty()) {
                UserNotification::query()
                    ->where('related_type', 'trip')
                    ->whereIn('related_id', $tripIds)
                    ->delete();
            }

            UserNotification::query()
                ->where('related_type', 'route')
                ->where('related_id', $savedRoute->id)
                ->delete();

            $savedRoute->delete();
        });
    }

    private function syncPassengerStops(SavedRoute $savedRoute, User $owner, array $stops): void
    {
        $allowedIds = Connection::acceptedUserIdsFor($owner)->flip();
        $normalized = collect($stops)
            ->map(fn ($stop) => is_array($stop) ? $stop : [])
            ->filter(fn (array $stop) => (int) ($stop['user_id'] ?? 0) > 0)
            ->map(function (array $stop) use ($savedRoute) {
                $userId = (int) $stop['user_id'];
                $pickupLat = $stop['pickup_latitude'] ?? null;
                $pickupLng = $stop['pickup_longitude'] ?? null;
                $dropoffLat = $stop['dropoff_latitude'] ?? null;
                $dropoffLng = $stop['dropoff_longitude'] ?? null;
                $extraFee = $stop['extra_fee_amount'] ?? null;

                return [
                    'user_id' => $userId,
                    'pickup_name' => trim((string) ($stop['pickup_name'] ?? '')) ?: $savedRoute->point_a_name,
                    'pickup_latitude' => $pickupLat !== null && $pickupLat !== '' ? $pickupLat : $savedRoute->point_a_latitude,
                    'pickup_longitude' => $pickupLng !== null && $pickupLng !== '' ? $pickupLng : $savedRoute->point_a_longitude,
                    'dropoff_name' => trim((string) ($stop['dropoff_name'] ?? '')) ?: $savedRoute->point_b_name,
                    'dropoff_latitude' => $dropoffLat !== null && $dropoffLat !== '' ? $dropoffLat : $savedRoute->point_b_latitude,
                    'dropoff_longitude' => $dropoffLng !== null && $dropoffLng !== '' ? $dropoffLng : $savedRoute->point_b_longitude,
                    'extra_fee_amount' => $extraFee !== null && $extraFee !== '' ? $extraFee : null,
                    'note' => trim((string) ($stop['note'] ?? '')) ?: null,
                    'is_active' => array_key_exists('is_active', $stop) ? (bool) $stop['is_active'] : true,
                ];
            })
            ->unique('user_id')
            ->values();

        $tooFar = $normalized->first(function (array $stop) use ($savedRoute): bool {
            $pickupDistance = $this->distanceKm(
                (float) $savedRoute->point_a_latitude,
                (float) $savedRoute->point_a_longitude,
                (float) $stop['pickup_latitude'],
                (float) $stop['pickup_longitude']
            );
            $dropoffDistance = $this->distanceKm(
                (float) $savedRoute->point_b_latitude,
                (float) $savedRoute->point_b_longitude,
                (float) $stop['dropoff_latitude'],
                (float) $stop['dropoff_longitude']
            );

            return $pickupDistance > 3 || $dropoffDistance > 3;
        });

        if ($tooFar) {
            throw ValidationException::withMessages([
                'passenger_stops' => 'Custom passenger stops must stay within 3 km of the saved route pickup/drop-off points.',
            ]);
        }

        $invalid = $normalized->filter(fn (array $stop) => ! $allowedIds->has($stop['user_id']));
        if ($invalid->isNotEmpty()) {
            throw ValidationException::withMessages([
                'passenger_stops' => 'Preset passengers must be selected from accepted connections.',
            ]);
        }

        $savedRoute->passengerStops()->delete();

        $normalized->each(fn (array $stop) => $savedRoute->passengerStops()->create($stop));
    }

    private function distanceKm(float $latA, float $lngA, float $latB, float $lngB): float
    {
        $earthRadiusKm = 6371;
        $deltaLat = deg2rad($latB - $latA);
        $deltaLng = deg2rad($lngB - $lngA);
        $a = sin($deltaLat / 2) ** 2
            + cos(deg2rad($latA)) * cos(deg2rad($latB)) * sin($deltaLng / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

}
