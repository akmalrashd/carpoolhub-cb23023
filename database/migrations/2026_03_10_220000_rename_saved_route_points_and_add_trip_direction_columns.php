<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->renameSavedRouteColumn('pickup_name', 'point_a_name');
        $this->renameSavedRouteColumn('pickup_latitude', 'point_a_latitude');
        $this->renameSavedRouteColumn('pickup_longitude', 'point_a_longitude');
        $this->renameSavedRouteColumn('destination_name', 'point_b_name');
        $this->renameSavedRouteColumn('destination_latitude', 'point_b_latitude');
        $this->renameSavedRouteColumn('destination_longitude', 'point_b_longitude');

        Schema::table('trips', function (Blueprint $table) {
            if (! Schema::hasColumn('trips', 'pickup_name')) {
                $table->string('pickup_name')->nullable()->after('saved_route_id');
            }
            if (! Schema::hasColumn('trips', 'pickup_latitude')) {
                $table->decimal('pickup_latitude', 10, 7)->nullable()->after('pickup_name');
            }
            if (! Schema::hasColumn('trips', 'pickup_longitude')) {
                $table->decimal('pickup_longitude', 10, 7)->nullable()->after('pickup_latitude');
            }
            if (! Schema::hasColumn('trips', 'destination_name')) {
                $table->string('destination_name')->nullable()->after('pickup_longitude');
            }
            if (! Schema::hasColumn('trips', 'destination_latitude')) {
                $table->decimal('destination_latitude', 10, 7)->nullable()->after('destination_name');
            }
            if (! Schema::hasColumn('trips', 'destination_longitude')) {
                $table->decimal('destination_longitude', 10, 7)->nullable()->after('destination_latitude');
            }
        });

        Schema::table('archived_trips', function (Blueprint $table) {
            if (! Schema::hasColumn('archived_trips', 'pickup_name')) {
                $table->string('pickup_name')->nullable()->after('saved_route_id');
            }
            if (! Schema::hasColumn('archived_trips', 'pickup_latitude')) {
                $table->decimal('pickup_latitude', 10, 7)->nullable()->after('pickup_name');
            }
            if (! Schema::hasColumn('archived_trips', 'pickup_longitude')) {
                $table->decimal('pickup_longitude', 10, 7)->nullable()->after('pickup_latitude');
            }
            if (! Schema::hasColumn('archived_trips', 'destination_name')) {
                $table->string('destination_name')->nullable()->after('pickup_longitude');
            }
            if (! Schema::hasColumn('archived_trips', 'destination_latitude')) {
                $table->decimal('destination_latitude', 10, 7)->nullable()->after('destination_name');
            }
            if (! Schema::hasColumn('archived_trips', 'destination_longitude')) {
                $table->decimal('destination_longitude', 10, 7)->nullable()->after('destination_latitude');
            }
        });

        $savedRoutes = DB::table('saved_routes')->get()->keyBy('id');

        DB::table('trips')->orderBy('id')->get()->each(function ($trip) use ($savedRoutes): void {
            $savedRoute = $savedRoutes->get($trip->saved_route_id);

            if (! $savedRoute) {
                return;
            }

            $isReturnTrip = (bool) ($trip->is_return_trip ?? false);

            DB::table('trips')
                ->where('id', $trip->id)
                ->update([
                    'pickup_name' => $isReturnTrip ? $savedRoute->point_b_name : $savedRoute->point_a_name,
                    'pickup_latitude' => $isReturnTrip ? $savedRoute->point_b_latitude : $savedRoute->point_a_latitude,
                    'pickup_longitude' => $isReturnTrip ? $savedRoute->point_b_longitude : $savedRoute->point_a_longitude,
                    'destination_name' => $isReturnTrip ? $savedRoute->point_a_name : $savedRoute->point_b_name,
                    'destination_latitude' => $isReturnTrip ? $savedRoute->point_a_latitude : $savedRoute->point_b_latitude,
                    'destination_longitude' => $isReturnTrip ? $savedRoute->point_a_longitude : $savedRoute->point_b_longitude,
                ]);
        });

        DB::table('archived_trips')->orderBy('id')->get()->each(function ($trip) use ($savedRoutes): void {
            $savedRoute = $savedRoutes->get($trip->saved_route_id);

            if (! $savedRoute) {
                return;
            }

            $isReturnTrip = (bool) ($trip->is_return_trip ?? false);

            DB::table('archived_trips')
                ->where('id', $trip->id)
                ->update([
                    'pickup_name' => $isReturnTrip ? $savedRoute->point_b_name : $savedRoute->point_a_name,
                    'pickup_latitude' => $isReturnTrip ? $savedRoute->point_b_latitude : $savedRoute->point_a_latitude,
                    'pickup_longitude' => $isReturnTrip ? $savedRoute->point_b_longitude : $savedRoute->point_a_longitude,
                    'destination_name' => $isReturnTrip ? $savedRoute->point_a_name : $savedRoute->point_b_name,
                    'destination_latitude' => $isReturnTrip ? $savedRoute->point_a_latitude : $savedRoute->point_b_latitude,
                    'destination_longitude' => $isReturnTrip ? $savedRoute->point_a_longitude : $savedRoute->point_b_longitude,
                ]);
        });
    }

    public function down(): void
    {
        $this->renameSavedRouteColumn('point_a_name', 'pickup_name');
        $this->renameSavedRouteColumn('point_a_latitude', 'pickup_latitude');
        $this->renameSavedRouteColumn('point_a_longitude', 'pickup_longitude');
        $this->renameSavedRouteColumn('point_b_name', 'destination_name');
        $this->renameSavedRouteColumn('point_b_latitude', 'destination_latitude');
        $this->renameSavedRouteColumn('point_b_longitude', 'destination_longitude');

        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn([
                'pickup_name',
                'pickup_latitude',
                'pickup_longitude',
                'destination_name',
                'destination_latitude',
                'destination_longitude',
            ]);
        });

        Schema::table('archived_trips', function (Blueprint $table) {
            $table->dropColumn([
                'pickup_name',
                'pickup_latitude',
                'pickup_longitude',
                'destination_name',
                'destination_latitude',
                'destination_longitude',
            ]);
        });
    }

    private function renameSavedRouteColumn(string $from, string $to): void
    {
        if (! Schema::hasColumn('saved_routes', $from) || Schema::hasColumn('saved_routes', $to)) {
            return;
        }

        Schema::table('saved_routes', function (Blueprint $table) use ($from, $to) {
            $table->renameColumn($from, $to);
        });
    }
};
