<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * All single-column / equality-composite indexes named in the original audit
 * (trips.status, trips.trip_datetime, trips.visibility, trip_payments.payment_status,
 * and the (visibility,status,is_open_for_request) public filter) were ALREADY added
 * by 2026_05_23_000000_add_performance_indexes.php. trips.parent_trip_id is indexed by
 * its ->constrained() FK (trips_parent_trip_id_foreign). Re-adding any of those would
 * create duplicate indexes, so this migration does NOT touch them.
 *
 * What remains justified: the two hottest ORDER BY trip_datetime paths still do a
 * "Using filesort" because no index carries the sort column as a trailing member of
 * the equality prefix they filter on. These two composites append trip_datetime so the
 * sort is served by the index (filesort removed; type range/ref preserved).
 *
 *  1. trips (visibility, status, is_open_for_request, trip_datetime)
 *     - TripService::paginateExplore()  (WHERE visibility='public' AND status='scheduled'
 *       AND is_open_for_request=1 AND trip_datetime>=NOW() ORDER BY trip_datetime)
 *     - DashboardController publicExploreTrips (same equality prefix + trip_datetime order)
 *
 *  2. trips (driver_id, trip_datetime)
 *     - TripService::baseUserTripsQuery() -> latest('trip_datetime')  (My Trips list)
 *     - DashboardController upcomingCreatedTrips (driver_id + trip_datetime ORDER BY)
 *
 * Indexes never change query RESULTS, only the plan — this migration is behaviour-preserving.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table): void {
            // `id` is the trailing member so the index serves the full
            // deterministic sort `ORDER BY trip_datetime, id` (the tiebreaker
            // added to every paginated trip_datetime query) with no filesort.
            $table->index(
                ['visibility', 'status', 'is_open_for_request', 'trip_datetime', 'id'],
                'trips_public_filter_datetime_idx'
            );
            $table->index(
                ['driver_id', 'trip_datetime', 'id'],
                'trips_driver_datetime_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table): void {
            $table->dropIndex('trips_public_filter_datetime_idx');
            $table->dropIndex('trips_driver_datetime_idx');
        });
    }
};
