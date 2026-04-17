<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("UPDATE trips SET status = 'scheduled' WHERE status = 'confirmed'");
        DB::statement("UPDATE trips SET status = 'recorded' WHERE status = 'completed'");

        DB::statement("
            ALTER TABLE trips
            MODIFY status ENUM('draft','scheduled','recorded','cancelled') NOT NULL DEFAULT 'draft'
        ");
    }

    public function down(): void
    {
        DB::statement("UPDATE trips SET status = 'confirmed' WHERE status = 'scheduled'");
        DB::statement("UPDATE trips SET status = 'completed' WHERE status = 'recorded'");

        DB::statement("
            ALTER TABLE trips
            MODIFY status ENUM('draft','confirmed','completed','cancelled') NOT NULL DEFAULT 'draft'
        ");
    }
};

