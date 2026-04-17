<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN phone_visible VARCHAR(24) NOT NULL DEFAULT 'visible_friend'");
        DB::statement("ALTER TABLE users MODIFY COLUMN email_visible VARCHAR(24) NOT NULL DEFAULT 'visible_friend'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN phone_visible VARCHAR(24) NOT NULL DEFAULT 'visible_public'");
        DB::statement("ALTER TABLE users MODIFY COLUMN email_visible VARCHAR(24) NOT NULL DEFAULT 'visible_public'");
    }
};

