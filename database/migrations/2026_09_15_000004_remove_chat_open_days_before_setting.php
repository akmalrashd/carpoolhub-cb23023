<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Chats no longer hold sending behind a "days before departure" window
     * (see ChatService::buildConversationAttributes()) — the setting this
     * seeded is dead now, so drop the row rather than leave a stale,
     * misleading one sitting in system_settings.
     */
    public function up(): void
    {
        DB::table('system_settings')->where('key', 'chat_open_days_before')->delete();
    }

    public function down(): void
    {
        DB::table('system_settings')->insert([
            'key' => 'chat_open_days_before',
            'value' => '3',
            'updated_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
