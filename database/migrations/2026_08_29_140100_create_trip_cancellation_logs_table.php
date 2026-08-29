<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TripService::delete() hard-deletes the trip row (and cascades its
     * participants/payments) with no history kept anywhere — a dispute like
     * "the driver cancelled 10 minutes before departure" currently has
     * nothing to check against. This table is written right before that
     * delete, so trip_id is deliberately a plain column, not a real FK: the
     * row it describes will be gone by the time anyone reads this, same
     * reasoning as trip_payment_status_logs' nullOnDelete() choice. The
     * snapshots capture the trip/participants/payments as they stood at the
     * moment of cancellation.
     */
    public function up(): void
    {
        Schema::create('trip_cancellation_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('trip_id');
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancelled_by_role')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('trip_datetime')->nullable();

            $table->json('trip_snapshot');
            $table->json('participants_snapshot')->nullable();
            $table->json('payments_snapshot')->nullable();

            $table->timestamp('created_at')->nullable();

            $table->index(['trip_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_cancellation_logs');
    }
};
