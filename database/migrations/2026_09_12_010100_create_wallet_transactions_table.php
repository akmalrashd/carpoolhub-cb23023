<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only ledger behind every wallets.balance change (gateway
     * payments, withdrawal reservations, withdrawal refunds, admin
     * adjustments). balance_after snapshots the running total at each entry
     * so a disputed balance can be checked entry-by-entry instead of trusting
     * a single mutable column. related_type/related_id is a loose pointer
     * (no FK), matching notifications.related_type/related_id already in
     * this codebase — it can point at a gateway_transactions or
     * withdrawal_requests row without a hard constraint either way.
     */
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('direction');
            $table->string('reason');
            $table->decimal('amount', 8, 2);
            $table->decimal('balance_after', 10, 2);

            $table->string('related_type')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();

            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('created_at')->nullable();

            $table->index(['wallet_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['related_type', 'related_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
