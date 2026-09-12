<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Admin-editable via the existing SystemSetting::get/set store (same
     * pattern as gateway_fee_flat_amount etc.) — how many days before a trip
     * its chat opens, and how many days after the trip ends/is cancelled it
     * gets purged.
     */
    public function up(): void
    {
        $now = now();
        DB::table('system_settings')->insert([
            ['key' => 'chat_open_days_before', 'value' => '3', 'updated_by' => null, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'chat_retention_days_after', 'value' => '3', 'updated_by' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        DB::table('system_settings')->whereIn('key', ['chat_open_days_before', 'chat_retention_days_after'])->delete();
    }
};
