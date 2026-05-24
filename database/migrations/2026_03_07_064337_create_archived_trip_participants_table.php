<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archived_trip_participants', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('archived_trip_id');

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->boolean('is_driver')->default(false);

            $table->decimal('fare_amount', 8, 2)->default(0);

            $table->enum('attendance_status', [
                'joined',
                'removed',
                'absent'
            ])->default('joined');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('attendance_marked_at')->nullable();
            $table->string('attendance_source', 50)->nullable();

            $table->timestamp('archived_at')->nullable();

            $table->timestamps();

            $table->index('archived_trip_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archived_trip_participants');
    }
};