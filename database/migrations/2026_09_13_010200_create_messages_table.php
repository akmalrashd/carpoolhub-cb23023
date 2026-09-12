<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only — no edit/delete in v1, matching the ledger-style tables
     * this app already has (wallet_transactions, trip_payment_status_logs):
     * updated_at is never used, see Message::UPDATED_AT = null.
     *
     * sender_id is nullOnDelete (not cascade) so a later account deletion
     * doesn't blow away the rest of the group's chat history — the message
     * just renders against a missing sender.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('type')->default('text');
            $table->text('body')->nullable();

            $table->dateTime('created_at')->nullable();

            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
