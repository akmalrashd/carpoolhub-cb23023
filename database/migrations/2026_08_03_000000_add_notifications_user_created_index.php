<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The notification bell polls every 5 seconds from every open tab, and its
 * query is:
 *
 *     select * from notifications where user_id = ? order by created_at desc limit 6
 *
 * (NotificationService::recentForUser — latest() sorts on created_at.)
 *
 * notifications already carries (user_id), (is_read), (user_id, is_read),
 * (related_type, related_id) and (user_id, type) — but nothing that covers the
 * ORDER BY. MySQL therefore reads every row for that user and filesorts them on
 * each poll. This composite lets it walk the index backwards and stop after 6.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->index(['user_id', 'created_at'], 'notifications_user_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropIndex('notifications_user_created_idx');
        });
    }
};
