<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add missing FK: trip_payments.confirmed_by → users.id
        Schema::table('trip_payments', function (Blueprint $table): void {
            $table->foreign('confirmed_by')
                ->references('id')->on('users')
                ->onDelete('set null');
        });

        // 2. ENUM: trip_passenger_route_points.status (was varchar(32))
        //    Values used: requested, accepted, rejected, cancelled, removed
        DB::statement("ALTER TABLE trip_passenger_route_points
            MODIFY status ENUM('requested','accepted','rejected','cancelled','removed')
            NOT NULL DEFAULT 'requested'");

        // 3. ENUM: passenger_risk_profiles.risk_level (was varchar(50))
        //    Values from config bands: Low Risk, Moderate Risk, High Risk, Very High Risk
        DB::statement("ALTER TABLE passenger_risk_profiles
            MODIFY risk_level ENUM('Low Risk','Moderate Risk','High Risk','Very High Risk')
            NOT NULL DEFAULT 'Moderate Risk'");

        // 4. ENUM: trip_strategy_suggestions.confidence_level (was varchar(30))
        //    Values used: Low, Medium, High
        DB::statement("ALTER TABLE trip_strategy_suggestions
            MODIFY confidence_level ENUM('Low','Medium','High')
            NOT NULL DEFAULT 'Low'");

        // 5. Missing indexes on users table
        Schema::table('users', function (Blueprint $table): void {
            $table->index('role', 'users_role_idx');
            $table->index('is_active', 'users_is_active_idx');
        });

        // 6. Missing index on saved_route_passenger_stops.is_active
        Schema::table('saved_route_passenger_stops', function (Blueprint $table): void {
            $table->index('is_active', 'saved_route_passenger_stops_is_active_idx');
        });

        // 7. Shrink notifications.related_type from varchar(255) to varchar(50)
        DB::statement("ALTER TABLE notifications
            MODIFY related_type VARCHAR(50) NULL");
    }

    public function down(): void
    {
        Schema::table('trip_payments', function (Blueprint $table): void {
            $table->dropForeign(['confirmed_by']);
        });

        DB::statement("ALTER TABLE trip_passenger_route_points
            MODIFY status VARCHAR(32) NOT NULL DEFAULT 'requested'");

        DB::statement("ALTER TABLE passenger_risk_profiles
            MODIFY risk_level VARCHAR(50) NOT NULL DEFAULT 'Moderate Risk'");

        DB::statement("ALTER TABLE trip_strategy_suggestions
            MODIFY confidence_level VARCHAR(30) NOT NULL DEFAULT 'Low'");

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_role_idx');
            $table->dropIndex('users_is_active_idx');
        });

        Schema::table('saved_route_passenger_stops', function (Blueprint $table): void {
            $table->dropIndex('saved_route_passenger_stops_is_active_idx');
        });

        DB::statement("ALTER TABLE notifications
            MODIFY related_type VARCHAR(255) NULL");
    }
};
