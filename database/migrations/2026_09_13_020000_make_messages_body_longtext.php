<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Widened to hold a compressed base64 image data URI (photo-in-chat) the
 * same way users.profile_photo/selfie_photo/driving_license_photo already
 * do — TEXT's ~64KB cap is too tight for that, LONGTEXT is this app's
 * established column type for inline base64 images.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            $table->longText('body')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            $table->text('body')->nullable()->change();
        });
    }
};
