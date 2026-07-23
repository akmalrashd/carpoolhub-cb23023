<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Images (profile photo, DuitNow / TnG QR) are now stored as compressed base64
 * data URIs in the DB, like selfie_photo and driving_license_photo already are.
 * These columns were varchar(255) (they held a storage path), which cannot hold
 * a data URI, so widen them to longText. Existing rows keep their old path value
 * — the User image accessors resolve either form.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->longText('profile_photo')->nullable()->change();
            $table->longText('payment_qr_duitnow')->nullable()->change();
            $table->longText('payment_qr_tng')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('profile_photo')->nullable()->change();
            $table->string('payment_qr_duitnow', 64)->nullable()->change();
            $table->string('payment_qr_tng', 64)->nullable()->change();
        });
    }
};
