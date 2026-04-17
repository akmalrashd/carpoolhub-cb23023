<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->enum('visibility', ['private', 'public'])
                ->default('private')
                ->after('trip_mode');

            $table->unsignedInteger('seat_limit')
                ->nullable()
                ->after('participant_count');

            $table->boolean('is_open_for_request')
                ->default(false)
                ->after('seat_limit');

            $table->text('public_note')
                ->nullable()
                ->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn(['visibility', 'seat_limit', 'is_open_for_request', 'public_note']);
        });
    }
};

