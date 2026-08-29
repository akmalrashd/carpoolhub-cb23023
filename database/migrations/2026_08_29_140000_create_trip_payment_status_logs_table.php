<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only trail for trip_payments.payment_status changes. Every
     * status transition in PaymentService (mark paid, confirm, reject,
     * reverse) currently overwrites the same row in place — a rejection or
     * reversal nulls marked_paid_at/confirmed_by/confirmed_at/payment_method/
     * remarks with nothing kept anywhere, so a disputed "I paid but the
     * driver rejected it" has no evidence to check. previous_state snapshots
     * exactly those fields as they stood right before this row logs the
     * change, so that evidence survives the overwrite.
     *
     * FKs use nullOnDelete() rather than cascadeOnDelete(): trip_payments
     * rows do get hard-deleted during trip edits/participant resyncs
     * (TripJoinRequestService, TripService), and this log must outlive that —
     * it's meant to still answer "what happened" after the row it describes
     * is gone, not disappear with it. payer_id/amount_due are also
     * denormalised onto the row for the same reason.
     */
    public function up(): void
    {
        Schema::create('trip_payment_status_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('trip_payment_id')->nullable()->constrained('trip_payments')->nullOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained('trips')->nullOnDelete();
            $table->foreignId('payer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount_due', 8, 2)->default(0);

            $table->string('from_status');
            $table->string('to_status');

            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role')->nullable();
            $table->text('reason')->nullable();
            $table->json('previous_state')->nullable();

            $table->timestamp('created_at')->nullable();

            $table->index(['trip_payment_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_payment_status_logs');
    }
};
