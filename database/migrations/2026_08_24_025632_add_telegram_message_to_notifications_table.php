<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Nullable — TelegramService falls back to `message` when absent.
            // Exists because Telegram can render a nicely formatted, multi-line
            // breakdown (HTML, line breaks) while the in-app views collapse
            // newlines and hard-truncate to 2 lines, so a notification that
            // needs real formatting (e.g. the monthly payment summary) needs
            // different content per channel, not just different styling of
            // the same text.
            $table->text('telegram_message')->nullable()->after('message');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('telegram_message');
        });
    }
};
