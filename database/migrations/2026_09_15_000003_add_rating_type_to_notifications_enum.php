<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * notifications.type gets a dedicated 'rating' value (⭐ in
     * TelegramService::TYPE_EMOJI) for driver-rating invites/reminders —
     * a first-class recurring category on par with trip/payment/chat,
     * rather than burying it under 'system' (generic 🔔) or 'chat'
     * (semantically wrong — this isn't about unread messages).
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
