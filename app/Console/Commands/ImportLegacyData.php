<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off migration from the old 5MCarpool app (plain PHP, InfinityFree) into
 * CarpoolHub.
 *
 * Shape of the old data, established by inspecting the dump:
 *
 *   - `locations` is not a place, it is a ROUTE: "A <sep> B" plus a fix_fee.
 *     It maps onto saved_routes, and `driver_locations` tells us which driver
 *     had saved which route, so each route is recreated per owner.
 *   - `trips` / `trip_passengers` / `payment_status` are all EMPTY. Every
 *     record lives in the archived_* tables (the old monthly "sum out" moved
 *     them). So the archive is the real history.
 *   - Place names are mojibake: the separator is a UTF-8 "left right arrow"
 *     that was stored through a latin1 connection. We never need to repair it,
 *     only to split on it, so we split on any run of non-ASCII bytes.
 *   - fix_fee is the whole-car ROUND TRIP price. CarpoolHub stores a one-way
 *     fare per leg and creates two trip rows for a two-way trip, so
 *     default_fare = fix_fee / 2 and each leg charges that once.
 */
class ImportLegacyData extends Command
{
    protected $signature = 'legacy:import
        {--dry-run : Roll the transaction back at the end and report what would have happened}';

    protected $description = 'Import users, routes, trips and payments from the old 5MCarpool database';

    /** @var array<string, array{lat: float, lng: float}> */
    private array $coords = [];

    /** @var array<int, int> legacy user id => new user id */
    private array $userMap = [];

    /** @var array<string, int> "driverId:locationId" => saved_route id */
    private array $routeMap = [];

    private array $stats = [];

    public function handle(): int
    {
        if (! $this->loadCoordinates()) {
            return self::FAILURE;
        }

        $this->line('Source: '.config('database.connections.legacy.database'));
        $this->line('Target: '.config('database.connections.mysql.database'));
        $this->newLine();

        DB::beginTransaction();

        try {
            $this->importUsers();
            $this->importRoutes();
            $this->importConnections();
            $this->importTrips();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Gagal, semua perubahan dibatalkan: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            DB::rollBack();
            $this->report();
            $this->newLine();
            $this->warn('DRY RUN - tiada apa disimpan. Jalankan tanpa --dry-run untuk betul-betul import.');

            return self::SUCCESS;
        }

        DB::commit();
        $this->report();

        return self::SUCCESS;
    }

    /**
     * Coordinates are supplied by hand because the old app never stored them
     * and most of these places are local nicknames no geocoder can resolve.
     */
    private function loadCoordinates(): bool
    {
        $path = storage_path('app/legacy/coordinates.json');

        if (! is_file($path)) {
            $this->error("Missing {$path}");

            return false;
        }

        $raw = json_decode(file_get_contents($path), true);

        if (! is_array($raw)) {
            $this->error('coordinates.json is not valid JSON.');

            return false;
        }

        $missing = [];

        foreach ($raw as $place => $value) {
            if (str_starts_with($place, '_')) {
                continue;
            }

            if (! isset($value['lat'], $value['lng'])) {
                $missing[] = $place;

                continue;
            }

            $this->coords[$place] = [
                'lat' => (float) $value['lat'],
                'lng' => (float) $value['lng'],
            ];
        }

        if ($missing !== []) {
            $this->error('Coordinates still empty for '.count($missing).' place(s):');
            foreach ($missing as $place) {
                $this->line('  - '.$place);
            }
            $this->newLine();
            $this->line('Fill these in storage/app/legacy/coordinates.json and run again.');

            return false;
        }

        return true;
    }

    private function importUsers(): void
    {
        $rows = DB::connection('legacy')->table('users')->orderBy('id')->get();

        foreach ($rows as $row) {
            $id = DB::table('users')->where('email', $row->email)->value('id');

            $payload = [
                'name' => $row->name,
                // Old app used PHP password_hash() with bcrypt, which is the
                // same algorithm Laravel verifies against - passwords survive.
                'password' => $row->password,
                'role' => $row->role ?: 'passenger',
                'phone' => $row->phone,
                'payment_account_number' => $row->bank_account_no,
                'is_active' => 1,
                'updated_at' => now(),
            ];

            if ($id) {
                DB::table('users')->where('id', $id)->update($payload);
                $this->bump('users_updated');
            } else {
                $id = DB::table('users')->insertGetId($payload + [
                    'email' => $row->email,
                    // Nobody was verified in the old app, but requiring
                    // re-verification would lock every migrated user out.
                    'email_verified_at' => $row->created_at,
                    'created_at' => $row->created_at,
                ]);
                $this->bump('users_created');
            }

            $this->userMap[$row->id] = $id;
        }
    }

    /**
     * One saved_route per (driver, location) pair, taken from driver_locations.
     * Verified against the dump: every archived trip's pair exists there, so no
     * trip can end up without a route.
     */
    private function importRoutes(): void
    {
        $locations = DB::connection('legacy')->table('locations')->get()->keyBy('id');
        $pairs = DB::connection('legacy')->table('driver_locations')->get();

        foreach ($pairs as $pair) {
            $location = $locations->get($pair->location_id);
            $ownerId = $this->userMap[$pair->driver_id] ?? null;

            if (! $location || ! $ownerId) {
                continue;
            }

            [$pointA, $pointB] = $this->splitRouteName($location->name);
            $a = $this->coords[$pointA];
            $b = $this->coords[$pointB];

            $routeId = DB::table('saved_routes')->insertGetId([
                'user_id' => $ownerId,
                'route_name' => $pointA.' - '.$pointB,
                'point_a_name' => $pointA,
                'point_a_latitude' => $a['lat'],
                'point_a_longitude' => $a['lng'],
                'point_b_name' => $pointB,
                'point_b_latitude' => $b['lat'],
                'point_b_longitude' => $b['lng'],
                // fix_fee covers the round trip; CarpoolHub charges per leg.
                'default_fare' => round(((float) $location->fix_fee) / 2, 2),
                'is_active' => 1,
                'created_at' => $pair->saved_at,
                'updated_at' => $pair->saved_at,
            ]);

            $this->routeMap[$pair->driver_id.':'.$pair->location_id] = $routeId;
            $this->bump('routes');
        }
    }

    private function importConnections(): void
    {
        $rows = DB::connection('legacy')->table('user_connections')->get();

        foreach ($rows as $row) {
            $requester = $this->userMap[$row->user_id] ?? null;
            $receiver = $this->userMap[$row->connected_user_id] ?? null;

            if (! $requester || ! $receiver || $requester === $receiver) {
                continue;
            }

            $exists = DB::table('connections')
                ->where('requester_id', $requester)
                ->where('receiver_id', $receiver)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('connections')->insert([
                'requester_id' => $requester,
                'receiver_id' => $receiver,
                'status' => $row->status === 'rejected' ? 'rejected' : $row->status,
                'responded_at' => $row->status === 'pending' ? null : $row->created_at,
                'created_at' => $row->created_at,
                'updated_at' => $row->created_at,
            ]);

            $this->bump('connections');
        }
    }

    /**
     * Each archived trip becomes two rows - outbound and return - sharing one
     * trip_ref, exactly as TripService does for a two_way trip. The app lists
     * only rows with a null parent_trip_id, so the user still sees one trip.
     */
    private function importTrips(): void
    {
        $trips = DB::connection('legacy')->table('archived_trips')->orderBy('id')->get();
        $locations = DB::connection('legacy')->table('locations')->get()->keyBy('id');

        foreach ($trips as $legacyTrip) {
            $driverId = $this->userMap[$legacyTrip->driver_id] ?? null;
            $routeId = $this->routeMap[$legacyTrip->driver_id.':'.$legacyTrip->location_id] ?? null;
            $location = $locations->get($legacyTrip->location_id);

            if (! $driverId || ! $routeId || ! $location) {
                $this->bump('trips_skipped');

                continue;
            }

            [$pointA, $pointB] = $this->splitRouteName($location->name);
            $a = $this->coords[$pointA];
            $b = $this->coords[$pointB];
            $fareTotal = round(((float) $location->fix_fee) / 2, 2);

            $participants = DB::connection('legacy')
                ->table('archived_trip_passengers')
                ->where('trip_id', $legacyTrip->id)
                ->get();

            $userIds = [];
            foreach ($participants as $participant) {
                $mapped = $this->userMap[$participant->user_id] ?? null;
                if ($mapped) {
                    $userIds[] = $mapped;
                }
            }

            // Seven archived trips have no passenger rows at all - they were
            // created after the final sum out. Keep the record, with just the
            // driver on board, rather than dropping the driver's history.
            if ($userIds === []) {
                $userIds = [$driverId];
                $this->bump('trips_without_passengers');
            }

            $userIds = array_values(array_unique($userIds));
            $count = count($userIds);
            // Private trips split across everyone aboard, driver included -
            // this mirrors resolveSplitCount() for the non-public branch.
            $farePerPerson = round($fareTotal / max(1, $count), 2);

            $outboundId = $this->insertTrip([
                'driver_id' => $driverId,
                'saved_route_id' => $routeId,
                'parent_trip_id' => null,
                'is_return_trip' => 0,
                'pickup_name' => $pointA,
                'pickup_latitude' => $a['lat'],
                'pickup_longitude' => $a['lng'],
                'destination_name' => $pointB,
                'destination_latitude' => $b['lat'],
                'destination_longitude' => $b['lng'],
            ], $legacyTrip, $fareTotal, $farePerPerson, $count);

            $tripRef = sprintf('TRP-%05d', $outboundId);
            DB::table('trips')->where('id', $outboundId)->update(['trip_ref' => $tripRef]);

            $returnId = $this->insertTrip([
                'driver_id' => $driverId,
                'saved_route_id' => $routeId,
                'parent_trip_id' => $outboundId,
                'is_return_trip' => 1,
                'trip_ref' => $tripRef,
                'pickup_name' => $pointB,
                'pickup_latitude' => $b['lat'],
                'pickup_longitude' => $b['lng'],
                'destination_name' => $pointA,
                'destination_latitude' => $a['lat'],
                'destination_longitude' => $a['lng'],
            ], $legacyTrip, $fareTotal, $farePerPerson, $count);

            foreach ([$outboundId, $returnId] as $tripId) {
                $this->insertParticipantsAndPayments($tripId, $driverId, $userIds, $farePerPerson, $legacyTrip);
            }

            $this->bump('trips');
        }
    }

    private function insertTrip(array $direction, object $legacyTrip, float $fareTotal, float $farePerPerson, int $count): int
    {
        return DB::table('trips')->insertGetId($direction + [
            'trip_datetime' => $legacyTrip->trip_date,
            'trip_mode' => 'two_way',
            'visibility' => 'private',
            'status' => 'recorded',
            'fare_total' => $fareTotal,
            'fare_per_person' => $farePerPerson,
            'participant_count' => $count,
            'seat_limit' => null,
            'is_open_for_request' => 0,
            'note' => $legacyTrip->note,
            'public_note' => null,
            'created_at' => $legacyTrip->created_at,
            'updated_at' => $legacyTrip->updated_at ?: $legacyTrip->created_at,
        ]);
    }

    private function insertParticipantsAndPayments(int $tripId, int $driverId, array $userIds, float $farePerPerson, object $legacyTrip): void
    {
        foreach ($userIds as $userId) {
            DB::table('trip_participants')->insert([
                'trip_id' => $tripId,
                'user_id' => $userId,
                'is_driver' => $userId === $driverId ? 1 : 0,
                'fare_amount' => $farePerPerson,
                'attendance_status' => 'joined',
                'joined_at' => $legacyTrip->created_at,
                'created_at' => $legacyTrip->created_at,
                'updated_at' => $legacyTrip->created_at,
            ]);

            DB::table('trip_payments')->insert([
                'trip_id' => $tripId,
                'user_id' => $userId,
                'amount_due' => $farePerPerson,
                // The old is_paid flag is unreliable: only 6 of 226 rows were
                // ever ticked, because settlement happened in cash and nobody
                // updated the app. Treated as settled on the owner's say-so.
                'payment_status' => 'paid',
                'marked_paid_at' => $legacyTrip->created_at,
                'confirmed_by' => $driverId,
                'confirmed_at' => $legacyTrip->created_at,
                'payment_method' => 'cash',
                'remarks' => 'Diimport dari 5MCarpool',
                'created_at' => $legacyTrip->created_at,
                'updated_at' => $legacyTrip->created_at,
            ]);

            $this->bump('participants');
        }
    }

    /**
     * "A <mojibake arrow> B" -> ['A', 'B']. The separator is the only
     * non-ASCII run in these names, so we do not need to repair the encoding.
     *
     * @return array{0: string, 1: string}
     */
    private function splitRouteName(string $name): array
    {
        $parts = preg_split('/\s*[^\x20-\x7E]+\s*/u', $name, 2);

        return [
            trim($parts[0] ?? $name),
            trim($parts[1] ?? $name),
        ];
    }

    private function bump(string $key): void
    {
        $this->stats[$key] = ($this->stats[$key] ?? 0) + 1;
    }

    private function report(): void
    {
        $this->newLine();
        $this->info('Import selesai.');

        foreach ($this->stats as $key => $value) {
            $this->line(str_pad($key, 26).$value);
        }
    }
}
