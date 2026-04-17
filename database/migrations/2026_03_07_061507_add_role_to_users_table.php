<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'driver', 'passenger'])
                ->default('passenger')
                ->after('password');

            $table->string('phone')->nullable()->after('role');
            $table->string('profile_photo')->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('profile_photo');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'phone', 'profile_photo', 'is_active']);
        });
    }
};