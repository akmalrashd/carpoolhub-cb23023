<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per (trip, rater) — a passenger rating the driver of a public
     * trip they actually rode on. driver_id is a snapshot (not derived via
     * a join back to trips) so "all ratings for driver X" never needs to
     * join trips at all. Scoped to public trips only at the application
     * layer (DriverRatingService::isEligibleToRate) — private/circle trip
     * passengers are the driver's own Connections already, so rating them
     * adds little value and is easy to game.
     */
    public function up(): void
    {
        Schema::create('driver_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('rater_user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('stars');
            $table->timestamps();

            $table->unique(['trip_id', 'rater_user_id']);
            $table->index('driver_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_ratings');
    }
};
