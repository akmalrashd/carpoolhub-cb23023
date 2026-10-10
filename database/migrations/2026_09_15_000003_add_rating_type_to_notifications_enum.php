<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * notifications.type gets a dedicated 'rating' value (⭐ in
     * TelegramService::TYPE_EMOJI) for the driver rating invites and
     * reminders. It is a recurring category in its own right, alongside trip,
     * payment and chat,
     * rather than burying it under 'system' (generic 🔔) or 'chat'
     * (which would be wrong, since this has nothing to do with unread
     * messages).
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE notifications MODIFY type ENUM('trip', 'payment', 'system', 'connection', 'route', 'chat', 'rating') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE notifications MODIFY type ENUM('trip', 'payment', 'system', 'connection', 'route', 'chat') NOT NULL");
    }
};
