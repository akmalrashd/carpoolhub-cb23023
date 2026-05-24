<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_routes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('route_name')->nullable();

            $table->string('point_a_name');
            $table->decimal('point_a_latitude', 10, 7);
            $table->decimal('point_a_longitude', 10, 7);

            $table->string('point_b_name');
            $table->decimal('point_b_latitude', 10, 7);
            $table->decimal('point_b_longitude', 10, 7);

            $table->decimal('default_fare', 8, 2)->default(0);

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_routes');
    }
};