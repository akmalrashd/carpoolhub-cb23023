<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_recommendation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->string('context_source', 50)->default('explore');
            $table->decimal('match_score', 6, 2)->default(0);
            $table->decimal('route_score', 6, 2)->default(0);
            $table->decimal('time_score', 6, 2)->default(0);
            $table->decimal('seat_score', 6, 2)->default(0);
            $table->decimal('connection_score', 6, 2)->default(0);
            $table->decimal('fare_score', 6, 2)->default(0);
            $table->decimal('history_score', 6, 2)->default(0);
            $table->json('explanation_json')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'context_source', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_recommendation_logs');
    }
};
