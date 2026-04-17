<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_strategy_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->nullable()->constrained('trips')->nullOnDelete();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('saved_route_id')->constrained('saved_routes')->cascadeOnDelete();
            $table->decimal('suggested_fare_total', 8, 2)->default(0);
            $table->decimal('suggested_fare_per_person', 8, 2)->default(0);
            $table->unsignedInteger('suggested_seat_limit')->default(1);
            $table->unsignedTinyInteger('demand_score')->default(0);
            $table->string('confidence_level', 30)->default('Low');
            $table->string('strategy_type', 30)->default('draft_form');
            $table->json('input_payload')->nullable();
            $table->json('explanation_json')->nullable();
            $table->timestamps();

            $table->index(['driver_id', 'saved_route_id', 'created_at'], 'trip_strategy_driver_route_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_strategy_suggestions');
    }
};
