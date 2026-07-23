<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NotificationService::counts() runs several `where('user_id', ?)->where('type', ?)`
 * queries on every notifications page load and header refresh. user_id alone is
 * indexed (the foreign key), but the composite (user_id, type) is not, so each
 * count scans the user's whole notification set. This adds the composite index.
 * Behaviour is unchanged — only the query plan improves.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'type'], 'notifications_user_id_type_index');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_user_id_type_index');
        });
    }
};
