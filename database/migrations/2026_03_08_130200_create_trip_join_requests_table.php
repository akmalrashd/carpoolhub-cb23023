<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_join_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->text('request_note')->nullable();
            $table->text('response_note')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->unsignedInteger('decision_duration_minutes')->nullable();
            $table->json('decision_feature_snapshot')->nullable();
            $table->timestamps();

            $table->unique(['trip_id', 'user_id']);
            $table->index(['status', 'trip_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_join_requests');
    }
};

