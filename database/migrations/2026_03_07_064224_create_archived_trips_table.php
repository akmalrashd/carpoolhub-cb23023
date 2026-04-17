<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archived_trips', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('original_trip_id');

            $table->foreignId('driver_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('saved_route_id')
                ->constrained('saved_routes')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('billing_cycle_id')->nullable();

            $table->dateTime('trip_datetime');

            $table->decimal('fare_total', 8, 2)->default(0);
            $table->decimal('fare_per_person', 8, 2)->default(0);

            $table->unsignedInteger('participant_count')->default(0);

            $table->text('note')->nullable();

            $table->timestamp('archived_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archived_trips');
    }
};