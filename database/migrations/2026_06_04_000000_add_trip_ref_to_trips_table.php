<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table): void {
            $table->string('trip_ref', 20)->nullable()->after('id');
            $table->index('trip_ref');
        });

        // Backfill outbound/one-way trips first (parent_trip_id IS NULL)
        DB::statement("
            UPDATE trips
            SET trip_ref = CONCAT('TRP-', LPAD(id, 5, '0'))
            WHERE parent_trip_id IS NULL
        ");

        // Return legs share the same trip_ref as their parent
        DB::statement("
            UPDATE trips t
            JOIN trips p ON t.parent_trip_id = p.id
            SET t.trip_ref = p.trip_ref
            WHERE t.parent_trip_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table): void {
            $table->dropIndex(['trip_ref']);
            $table->dropColumn('trip_ref');
        });
    }
};
