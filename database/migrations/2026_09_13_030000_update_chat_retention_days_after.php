<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Raised from 3 to 30 days: a passenger reporting a scam needs time to
     * notice something is wrong, export the chat, and file the report
     * before the admin has anything left to check it against. Three days after
     * the trip ends was nowhere near enough time for that.
     */
    public function up(): void
    {
        DB::table('system_settings')
            ->where('key', 'chat_retention_days_after')
            ->update(['value' => '30', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('system_settings')
            ->where('key', 'chat_retention_days_after')
            ->update(['value' => '3', 'updated_at' => now()]);
    }
};
