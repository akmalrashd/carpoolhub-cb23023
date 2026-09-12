<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A driver's request to cash out their wallet balance. destination_* is a
     * snapshot of users.payment_bank_name/payment_account_name/
     * payment_account_number taken at request time — a later edit to the
     * driver's Settings must never silently rewrite a historical request the
     * admin already reviewed or paid.
     *
     * Only 3 states (pending/paid/rejected), no separate "approved" limbo:
     * the admin's manual bank transfer and marking it paid are one
     * combined action, matching TripPayment's 2-outcome confirm/reject shape
     * rather than inventing a 3-outcome pipeline with no precedent here.
     */
    public function up(): void
    {
        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 8, 2);

            $table->string('destination_bank_name');
            $table->string('destination_account_name');
            $table->string('destination_account_number');

            $table->string('status')->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->text('admin_remarks')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_requests');
    }
};
