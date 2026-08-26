<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('email');
        });

        // A Google-only account has no password to hash — native ->nullable()
        // ->change() needs doctrine/dbal, which this project doesn't install,
        // so this goes straight through the query builder instead.
        DB::statement('ALTER TABLE users MODIFY password VARCHAR(255) NULL');

        // This migration is what makes email verification enforced (the
        // 'verified' middleware now gates the whole app) — without this,
        // every account that registered before today, none of which was ever
        // asked to verify anything, gets locked out the moment this deploys.
        // Grandfather them in as of when they joined.
        DB::table('users')->whereNull('email_verified_at')->update([
            'email_verified_at' => DB::raw('created_at'),
        ]);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users MODIFY password VARCHAR(255) NOT NULL');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('google_id');
        });
    }
};
