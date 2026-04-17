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
        Schema::table('users', function (Blueprint $table) {
            $table->string('payment_account_name')->nullable()->after('profile_photo');
            $table->string('payment_account_number')->nullable()->after('payment_account_name');
            $table->string('payment_qr_duitnow')->nullable()->after('payment_account_number');
            $table->string('payment_qr_tng')->nullable()->after('payment_qr_duitnow');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'payment_account_name',
                'payment_account_number',
                'payment_qr_duitnow',
                'payment_qr_tng',
            ]);
        });
    }
};

