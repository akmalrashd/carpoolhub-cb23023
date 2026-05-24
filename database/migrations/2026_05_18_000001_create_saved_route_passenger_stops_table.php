<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_route_passenger_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saved_route_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('pickup_name')->nullable();
            $table->decimal('pickup_latitude', 10, 7)->nullable();
            $table->decimal('pickup_longitude', 10, 7)->nullable();
            $table->string('dropoff_name')->nullable();
            $table->decimal('dropoff_latitude', 10, 7)->nullable();
            $table->decimal('dropoff_longitude', 10, 7)->nullable();
            $table->decimal('extra_fee_amount', 8, 2)->nullable();
            $table->string('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['saved_route_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_route_passenger_stops');
    }
};
