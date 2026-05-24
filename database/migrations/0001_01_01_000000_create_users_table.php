<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('email_visible', 24)->default('visible_friend');
            $table->string('password');
            $table->rememberToken();
            $table->enum('role', ['admin', 'driver', 'passenger'])->default('passenger');
            $table->string('phone')->nullable();
            $table->string('phone_visible', 24)->default('visible_friend');
            $table->string('vehicle_model')->nullable();
            $table->string('vehicle_plate')->nullable();
            $table->string('profile_photo')->nullable();
            $table->string('payment_account_name')->nullable();
            $table->string('payment_account_number')->nullable();
            $table->string('payment_bank_name')->nullable();
            $table->string('payment_qr_duitnow')->nullable();
            $table->string('payment_qr_tng')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
