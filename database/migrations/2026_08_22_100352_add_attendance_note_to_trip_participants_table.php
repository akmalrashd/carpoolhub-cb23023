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
            // Holds the reason for the two attendance_status changes that can
            // carry one. 'removed' always needs a reason from the driver, while
            // 'absent' does not, which is why the column stays nullable.
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
