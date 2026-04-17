<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_join_requests', function (Blueprint $table) {
            $table->unsignedInteger('decision_duration_minutes')->nullable()->after('responded_at');
            $table->json('decision_feature_snapshot')->nullable()->after('decision_duration_minutes');
        });

        Schema::table('trip_participants', function (Blueprint $table) {
            $table->timestamp('joined_at')->nullable()->after('attendance_status');
            $table->timestamp('cancelled_at')->nullable()->after('joined_at');
            $table->timestamp('attendance_marked_at')->nullable()->after('cancelled_at');
            $table->string('attendance_source', 50)->nullable()->after('attendance_marked_at');
        });

        Schema::table('archived_trip_participants', function (Blueprint $table) {
            $table->timestamp('joined_at')->nullable()->after('attendance_status');
            $table->timestamp('cancelled_at')->nullable()->after('joined_at');
            $table->timestamp('attendance_marked_at')->nullable()->after('cancelled_at');
            $table->string('attendance_source', 50)->nullable()->after('attendance_marked_at');
        });

        DB::table('trip_join_requests')
            ->whereNotNull('responded_at')
            ->orderBy('id')
            ->get(['id', 'created_at', 'responded_at'])
            ->each(function (object $row): void {
                $minutes = Carbon::parse($row->created_at)->diffInMinutes(Carbon::parse($row->responded_at), false);

                DB::table('trip_join_requests')
                    ->where('id', $row->id)
                    ->update([
                        'decision_duration_minutes' => max(0, $minutes),
                    ]);
            });

        DB::table('trip_participants')
            ->update([
                'joined_at' => DB::raw('COALESCE(joined_at, created_at)'),
                'attendance_marked_at' => DB::raw("CASE WHEN attendance_status <> 'joined' THEN updated_at ELSE attendance_marked_at END"),
            ]);

        DB::table('archived_trip_participants')
            ->update([
                'joined_at' => DB::raw('COALESCE(joined_at, created_at)'),
                'attendance_marked_at' => DB::raw("CASE WHEN attendance_status <> 'joined' THEN updated_at ELSE attendance_marked_at END"),
            ]);
    }

    public function down(): void
    {
        Schema::table('trip_join_requests', function (Blueprint $table) {
            $table->dropColumn(['decision_duration_minutes', 'decision_feature_snapshot']);
        });

        Schema::table('trip_participants', function (Blueprint $table) {
            $table->dropColumn(['joined_at', 'cancelled_at', 'attendance_marked_at', 'attendance_source']);
        });

        Schema::table('archived_trip_participants', function (Blueprint $table) {
            $table->dropColumn(['joined_at', 'cancelled_at', 'attendance_marked_at', 'attendance_source']);
        });
    }
};
