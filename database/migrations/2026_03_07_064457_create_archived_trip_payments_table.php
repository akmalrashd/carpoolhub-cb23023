<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archived_trip_payments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('archived_trip_id');

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->decimal('amount_due', 8, 2)->default(0);

            $table->enum('payment_status', [
                'unpaid',
                'pending_confirmation',
                'paid'
            ])->default('unpaid');

            $table->timestamp('marked_paid_at')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->text('remarks')->nullable();

            $table->timestamp('archived_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archived_trip_payments');
    }
};