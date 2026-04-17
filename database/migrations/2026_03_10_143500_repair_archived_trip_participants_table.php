<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('archived_trip_participants', function (Blueprint $table) {
            if (! Schema::hasColumn('archived_trip_participants', 'archived_trip_id')) {
                $table->unsignedBigInteger('archived_trip_id')->nullable()->after('id');
            }

            if (! Schema::hasColumn('archived_trip_participants', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('archived_trip_id');
            }

            if (! Schema::hasColumn('archived_trip_participants', 'is_driver')) {
                $table->boolean('is_driver')->default(false)->after('user_id');
            }

            if (! Schema::hasColumn('archived_trip_participants', 'fare_amount')) {
                $table->decimal('fare_amount', 8, 2)->default(0)->after('is_driver');
            }

            if (! Schema::hasColumn('archived_trip_participants', 'attendance_status')) {
                $table->enum('attendance_status', ['joined', 'removed', 'absent'])->default('joined')->after('fare_amount');
            }

            if (! Schema::hasColumn('archived_trip_participants', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('attendance_status');
            }
        });

        Schema::table('archived_trip_participants', function (Blueprint $table) {
            if (Schema::hasColumn('archived_trip_participants', 'user_id')) {
                $table->index('user_id');
            }

            if (Schema::hasColumn('archived_trip_participants', 'archived_trip_id')) {
                $table->index('archived_trip_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('archived_trip_participants', function (Blueprint $table) {
            if (Schema::hasColumn('archived_trip_participants', 'archived_at')) {
                $table->dropColumn('archived_at');
            }

            if (Schema::hasColumn('archived_trip_participants', 'attendance_status')) {
                $table->dropColumn('attendance_status');
            }

            if (Schema::hasColumn('archived_trip_participants', 'fare_amount')) {
                $table->dropColumn('fare_amount');
            }

            if (Schema::hasColumn('archived_trip_participants', 'is_driver')) {
                $table->dropColumn('is_driver');
            }

            if (Schema::hasColumn('archived_trip_participants', 'user_id')) {
                $table->dropColumn('user_id');
            }

            if (Schema::hasColumn('archived_trip_participants', 'archived_trip_id')) {
                $table->dropColumn('archived_trip_id');
            }
        });
    }
};
