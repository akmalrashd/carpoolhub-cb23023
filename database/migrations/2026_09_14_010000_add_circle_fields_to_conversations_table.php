<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A "circle" is a persistent, driver-owned Conversation that isn't tied
     * to a single trip's lifecycle — it can be relinked across many private
     * trips over time (see ChatService::createCircle/linkCircleToTrip) and
     * is excluded from PurgeExpiredConversations entirely (only its old
     * messages get pruned, by PruneCircleMessages, not the conversation
     * itself). is_circle defaults to false (not nullable) since the purge
     * job's exclusion queries need an unambiguous value to filter on. name
     * is shown instead of route_snapshot for a circle, since one circle can
     * span many different routes over its life.
     */
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->boolean('is_circle')->default(false)->after('purge_reason');
            $table->string('name')->nullable()->after('is_circle');

            $table->index(['driver_id', 'is_circle']);
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex(['driver_id', 'is_circle']);
            $table->dropColumn(['is_circle', 'name']);
        });
    }
};
