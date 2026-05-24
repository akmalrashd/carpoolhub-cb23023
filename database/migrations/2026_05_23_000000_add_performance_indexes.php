<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->index('driver_id', 'trips_driver_id_idx');
            $table->index('status', 'trips_status_idx');
            $table->index('trip_datetime', 'trips_trip_datetime_idx');
            $table->index('billing_cycle_id', 'trips_billing_cycle_id_idx');
            $table->index('visibility', 'trips_visibility_idx');
            $table->index('is_open_for_request', 'trips_is_open_for_request_idx');
            $table->index(['status', 'trip_datetime'], 'trips_status_datetime_idx');
            $table->index(['visibility', 'status', 'is_open_for_request'], 'trips_public_filter_idx');
        });

        Schema::table('trip_payments', function (Blueprint $table) {
            $table->index('user_id', 'trip_payments_user_id_idx');
            $table->index('payment_status', 'trip_payments_payment_status_idx');
            $table->index(['user_id', 'payment_status'], 'trip_payments_user_status_idx');
        });

        Schema::table('trip_participants', function (Blueprint $table) {
            $table->index('user_id', 'trip_participants_user_id_idx');
            $table->index('is_driver', 'trip_participants_is_driver_idx');
            $table->index(['trip_id', 'is_driver'], 'trip_participants_trip_driver_idx');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index('user_id', 'notifications_user_id_idx');
            $table->index('is_read', 'notifications_is_read_idx');
            $table->index(['user_id', 'is_read'], 'notifications_user_read_idx');
            $table->index(['related_type', 'related_id'], 'notifications_related_idx');
        });

        Schema::table('connections', function (Blueprint $table) {
            $table->index('status', 'connections_status_idx');
            $table->index(['requester_id', 'status'], 'connections_requester_status_idx');
            $table->index(['receiver_id', 'status'], 'connections_receiver_status_idx');
        });

        Schema::table('saved_routes', function (Blueprint $table) {
            $table->index('user_id', 'saved_routes_user_id_idx');
            $table->index('is_active', 'saved_routes_is_active_idx');
            $table->index(['user_id', 'is_active'], 'saved_routes_user_active_idx');
        });

        Schema::table('trip_join_requests', function (Blueprint $table) {
            $table->index('user_id', 'trip_join_requests_user_id_idx');
        });

        Schema::table('archived_trips', function (Blueprint $table) {
            $table->index('driver_id', 'archived_trips_driver_id_idx');
            $table->index('billing_cycle_id', 'archived_trips_billing_cycle_id_idx');
        });

        Schema::table('archived_trip_payments', function (Blueprint $table) {
            $table->index('user_id', 'archived_trip_payments_user_id_idx');
            $table->index('payment_status', 'archived_trip_payments_status_idx');
            $table->index('archived_trip_id', 'archived_trip_payments_trip_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropIndex('trips_driver_id_idx');
            $table->dropIndex('trips_status_idx');
            $table->dropIndex('trips_trip_datetime_idx');
            $table->dropIndex('trips_billing_cycle_id_idx');
            $table->dropIndex('trips_visibility_idx');
            $table->dropIndex('trips_is_open_for_request_idx');
            $table->dropIndex('trips_status_datetime_idx');
            $table->dropIndex('trips_public_filter_idx');
        });

        Schema::table('trip_payments', function (Blueprint $table) {
            $table->dropIndex('trip_payments_user_id_idx');
            $table->dropIndex('trip_payments_payment_status_idx');
            $table->dropIndex('trip_payments_user_status_idx');
        });

        Schema::table('trip_participants', function (Blueprint $table) {
            $table->dropIndex('trip_participants_user_id_idx');
            $table->dropIndex('trip_participants_is_driver_idx');
            $table->dropIndex('trip_participants_trip_driver_idx');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_user_id_idx');
            $table->dropIndex('notifications_is_read_idx');
            $table->dropIndex('notifications_user_read_idx');
            $table->dropIndex('notifications_related_idx');
        });

        Schema::table('connections', function (Blueprint $table) {
            $table->dropIndex('connections_status_idx');
            $table->dropIndex('connections_requester_status_idx');
            $table->dropIndex('connections_receiver_status_idx');
        });

        Schema::table('saved_routes', function (Blueprint $table) {
            $table->dropIndex('saved_routes_user_id_idx');
            $table->dropIndex('saved_routes_is_active_idx');
            $table->dropIndex('saved_routes_user_active_idx');
        });

        Schema::table('trip_join_requests', function (Blueprint $table) {
            $table->dropIndex('trip_join_requests_user_id_idx');
        });

        Schema::table('archived_trips', function (Blueprint $table) {
            $table->dropIndex('archived_trips_driver_id_idx');
            $table->dropIndex('archived_trips_billing_cycle_id_idx');
        });

        Schema::table('archived_trip_payments', function (Blueprint $table) {
            $table->dropIndex('archived_trip_payments_user_id_idx');
            $table->dropIndex('archived_trip_payments_status_idx');
            $table->dropIndex('archived_trip_payments_trip_id_idx');
        });
    }
};
