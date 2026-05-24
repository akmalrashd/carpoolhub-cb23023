<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();

            $table->foreignId('driver_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('saved_route_id')
                ->constrained('saved_routes')
                ->cascadeOnDelete();

            $table->foreignId('parent_trip_id')
                ->nullable()
                ->constrained('trips')
                ->nullOnDelete();

            $table->unsignedBigInteger('billing_cycle_id')->nullable();

            $table->string('pickup_name')->nullable();
            $table->decimal('pickup_latitude', 10, 7)->nullable();
            $table->decimal('pickup_longitude', 10, 7)->nullable();
            $table->string('destination_name')->nullable();
            $table->decimal('destination_latitude', 10, 7)->nullable();
            $table->decimal('destination_longitude', 10, 7)->nullable();

            $table->dateTime('trip_datetime');

            $table->enum('trip_mode', ['one_way', 'two_way'])->default('one_way');
            $table->boolean('is_return_trip')->default(false);

            $table->enum('visibility', ['private', 'public'])->default('private');

            $table->enum('status', ['draft', 'scheduled', 'recorded', 'cancelled'])->default('draft');

            $table->decimal('fare_total', 8, 2)->default(0);
            $table->decimal('fare_per_person', 8, 2)->default(0);

            $table->unsignedInteger('participant_count')->default(0);
            $table->unsignedInteger('seat_limit')->nullable();
            $table->boolean('is_open_for_request')->default(false);

            $table->text('note')->nullable();
            $table->text('public_note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};