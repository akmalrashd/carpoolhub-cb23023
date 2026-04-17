<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('archived_trips', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_trip_id')
                ->nullable()
                ->after('saved_route_id');

            $table->enum('trip_mode', ['one_way', 'two_way'])
                ->default('one_way')
                ->after('trip_datetime');

            $table->boolean('is_return_trip')
                ->default(false)
                ->after('trip_mode');
        });
    }

    public function down(): void
    {
        Schema::table('archived_trips', function (Blueprint $table) {
            $table->dropColumn(['parent_trip_id', 'trip_mode', 'is_return_trip']);
        });
    }
};
