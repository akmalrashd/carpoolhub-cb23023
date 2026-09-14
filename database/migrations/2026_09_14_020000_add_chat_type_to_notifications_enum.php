<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * notifications.type was a fixed enum (trip/payment/system/connection/
     * route) with nothing for chat — SendUnreadChatReminder needs its own
     * value so TelegramService's emoji lookup (💬) and any future chat
     * notification can use it instead of overloading 'system'.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE notifications MODIFY type ENUM('trip', 'payment', 'system', 'connection', 'route', 'chat') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE notifications MODIFY type ENUM('trip', 'payment', 'system', 'connection', 'route') NOT NULL");
    }
};
