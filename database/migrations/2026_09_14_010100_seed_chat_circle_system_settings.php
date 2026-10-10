<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * How many persistent circles a driver may own at once (UX clarity /
     * light abuse prevention rather than a storage concern, since retiring one
     * is a
     * single tap, see ChatService::deleteCircle), and how many days of
     * message history a circle keeps before old messages are pruned (the
     * circle/membership itself is never deleted by age, only by the owner
     * explicitly retiring it, which PruneCircleMessages handles).
     */
    public function up(): void
    {
        $now = now();
        DB::table('system_settings')->insert([
            ['key' => 'chat_max_circles_per_driver', 'value' => '5', 'updated_by' => null, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'chat_circle_message_retention_days', 'value' => '60', 'updated_by' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        DB::table('system_settings')->whereIn('key', ['chat_max_circles_per_driver', 'chat_circle_message_retention_days'])->delete();
    }
};
