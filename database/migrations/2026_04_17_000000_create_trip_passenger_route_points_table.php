<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_passenger_route_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trip_join_request_id')->nullable()->constrained('trip_join_requests')->nullOnDelete();
            $table->foreignId('trip_participant_id')->nullable()->constrained('trip_participants')->nullOnDelete();
            $table->string('pickup_name')->nullable();
            $table->decimal('pickup_latitude', 10, 7)->nullable();
            $table->decimal('pickup_longitude', 10, 7)->nullable();
            $table->string('dropoff_name')->nullable();
            $table->decimal('dropoff_latitude', 10, 7)->nullable();
            $table->decimal('dropoff_longitude', 10, 7)->nullable();
            $table->boolean('uses_default_pickup')->default(true);
            $table->boolean('uses_default_dropoff')->default(true);
            $table->dateTime('requested_pickup_time')->nullable();
            $table->unsignedTinyInteger('route_fit_score')->nullable();
            $table->string('route_fit_label', 80)->nullable();
            $table->decimal('pickup_distance_km', 8, 2)->nullable();
            $table->decimal('dropoff_distance_km', 8, 2)->nullable();
            $table->decimal('detour_distance_km', 8, 2)->nullable();
            $table->unsignedInteger('detour_duration_minutes')->nullable();
            $table->decimal('extra_fee_amount', 8, 2)->nullable();
            $table->string('status', 32)->default('requested');
            $table->timestamps();

            $table->index(['trip_id', 'user_id']);
            $table->index(['trip_join_request_id', 'trip_participant_id'], 'route_points_source_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_passenger_route_points');
    }
};
