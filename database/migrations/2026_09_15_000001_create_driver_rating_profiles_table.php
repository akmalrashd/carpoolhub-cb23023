<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A driver's cached aggregate rating — one row per driver, mirroring
     * PassengerRiskProfile's shape (a separate table, not columns bolted
     * onto users). rating_sum is the real source of truth (an exact
     * integer); rating_average is a derived cache recomputed from
     * rating_sum/rating_count on every write (DriverRatingService), not an
     * incrementally-updated float that could drift over years of ratings.
     */
    public function up(): void
    {
        Schema::create('driver_rating_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('rating_count')->default(0);
            $table->unsignedInteger('rating_sum')->default(0);
            $table->decimal('rating_average', 3, 2)->default(0.00);
            $table->timestamp('last_rated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_rating_profiles');
    }
};
