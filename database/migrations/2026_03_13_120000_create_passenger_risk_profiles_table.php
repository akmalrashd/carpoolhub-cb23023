<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passenger_risk_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('risk_score')->default(0);
            $table->string('risk_level', 50)->default('Moderate Risk');
            $table->decimal('payment_reliability_score', 3, 1)->default(5.0);
            $table->unsignedInteger('join_request_count')->default(0);
            $table->unsignedInteger('approved_request_count')->default(0);
            $table->unsignedInteger('rejected_request_count')->default(0);
            $table->unsignedInteger('cancelled_request_count')->default(0);
            $table->unsignedInteger('attendance_absent_count')->default(0);
            $table->decimal('outstanding_amount', 10, 2)->default(0);
            $table->unsignedInteger('overdue_case_count')->default(0);
            $table->decimal('avg_payment_delay_hours', 8, 2)->default(0);
            $table->timestamp('last_scored_at')->nullable();
            $table->json('feature_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passenger_risk_profiles');
    }
};
