<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per ToyyibPay bill attempt (a passenger may retry after a
     * failed/expired bill, so trip_payment_id is deliberately not unique).
     *
     * FKs use nullOnDelete() + denormalised trip_id/payer_id/driver_id,
     * mirroring trip_payment_status_logs exactly: trip_payments rows are
     * hard-deleted and rebuilt whenever a trip is edited
     * (TripService::syncParticipantsAndPayments()), so this row must be able
     * to survive that and still know who to credit.
     */
    public function up(): void
    {
        Schema::create('gateway_transactions', function (Blueprint $table) {
            $table->id();

            $table->string('gateway')->default('toyyibpay');
            $table->string('environment');

            $table->foreignId('trip_payment_id')->nullable()->constrained('trip_payments')->nullOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained('trips')->nullOnDelete();
            $table->foreignId('payer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('order_id')->unique();
            $table->string('bill_code')->nullable()->unique();

            $table->decimal('amount_due', 8, 2);
            $table->decimal('gateway_fee', 8, 2)->default(0);
            $table->decimal('amount_charged', 8, 2);
            $table->decimal('amount_credited', 8, 2)->nullable();

            $table->string('status')->default('pending');
            $table->string('toyyibpay_refno')->nullable();
            $table->text('error_message')->nullable();
            $table->json('raw_callback_payload')->nullable();
            $table->string('finalized_via')->nullable();
            $table->timestamp('finalized_at')->nullable();

            $table->timestamps();

            $table->index('trip_payment_id');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gateway_transactions');
    }
};
