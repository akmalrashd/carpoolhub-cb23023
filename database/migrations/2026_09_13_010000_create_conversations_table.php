<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per trip that has a chat. trip_id is nullOnDelete (not
     * cascade) because TripService::delete() hard-deletes the trip row the
     * moment a driver cancels, but the conversation must survive that for
     * the post-cancellation grace period — so trip_ref_snapshot/route_snapshot/
     * trip_datetime_snapshot are copied at creation time, the same
     * "denormalise because the source row can vanish or get rebuilt"
     * pattern already used for gateway_transactions.trip_id.
     *
     * opens_at/scheduled_purge_at drive the "appears a few days before the
     * trip, disappears a few days after it ends or is cancelled" requirement
     * without needing a soft-delete or extra status enum: a conversation is
     * simply purged once scheduled_purge_at passes (see
     * PurgeExpiredConversations), and hidden from its own UI until opens_at.
     */
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('trip_id')->nullable()->constrained('trips')->nullOnDelete();
            $table->string('trip_ref_snapshot')->nullable();
            $table->string('route_snapshot')->nullable();
            $table->dateTime('trip_datetime_snapshot')->nullable();
            $table->string('visibility_snapshot')->default('public');
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();

            $table->dateTime('opens_at')->nullable();
            $table->dateTime('scheduled_purge_at')->nullable();
            $table->string('purge_reason')->nullable();

            $table->timestamps();

            $table->index('trip_id');
            $table->index('scheduled_purge_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
