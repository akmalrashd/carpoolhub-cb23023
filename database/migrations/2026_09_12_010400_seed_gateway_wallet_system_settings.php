<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Admin-editable via the existing SystemSetting::get/set store (same
     * pattern as the 4 fuel-price keys) — these are tunable business/pricing
     * decisions, not secrets, so they don't belong in config/.env.
     */
    public function up(): void
    {
        $now = now();
        DB::table('system_settings')->insert([
            ['key' => 'gateway_fee_flat_amount', 'value' => '1.00', 'updated_by' => null, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'wallet_min_withdrawal_amount', 'value' => '10.00', 'updated_by' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        DB::table('system_settings')->whereIn('key', ['gateway_fee_flat_amount', 'wallet_min_withdrawal_amount'])->delete();
    }
};
