<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per driver — the single lockable balance that ToyyibPay
     * payments credit and withdrawals debit (see WalletService). No
     * lifetime_earned/withdrawn counters: those are computed on read from
     * wallet_transactions so a cached total can never drift from the ledger.
     */
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->decimal('balance', 10, 2)->default(0);
            $table->timestamps();
        });

        // Backfill every existing driver so WalletService::walletFor() never
        // has to race a lazy first-insert against a concurrent credit.
        $now = now();
        $driverIds = DB::table('users')->where('role', 'driver')->pluck('id');

        if ($driverIds->isNotEmpty()) {
            DB::table('wallets')->insert(
                $driverIds->map(fn (int $id): array => [
                    'user_id' => $id,
                    'balance' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all()
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
