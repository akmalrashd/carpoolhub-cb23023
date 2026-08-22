<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trip_participants', function (Blueprint $table) {
            // Reason text for the two attendance_status writes that need one —
            // 'removed' (driver-stated, mandatory) and 'absent' (no reason
            // required, so this stays nullable for that case).
            $table->text('attendance_note')->nullable()->after('attendance_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trip_participants', function (Blueprint $table) {
            $table->dropColumn('attendance_note');
        });
    }
};
