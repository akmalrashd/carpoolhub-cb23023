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
        // Convert legacy boolean flags into 3-level visibility values.
        DB::statement("ALTER TABLE users MODIFY COLUMN phone_visible VARCHAR(24) NOT NULL DEFAULT 'visible_public'");
        DB::statement("ALTER TABLE users MODIFY COLUMN email_visible VARCHAR(24) NOT NULL DEFAULT 'visible_public'");

        DB::statement("
            UPDATE users
            SET phone_visible = CASE
                WHEN phone_visible IN ('1', 'true', 'TRUE', 'visible_public', 'visible_friend', 'unvisible') THEN
                    CASE
                        WHEN phone_visible IN ('visible_public', 'visible_friend', 'unvisible') THEN phone_visible
                        ELSE 'visible_public'
                    END
                ELSE 'unvisible'
            END
        ");

        DB::statement("
            UPDATE users
            SET email_visible = CASE
                WHEN email_visible IN ('1', 'true', 'TRUE', 'visible_public', 'visible_friend', 'unvisible') THEN
                    CASE
                        WHEN email_visible IN ('visible_public', 'visible_friend', 'unvisible') THEN email_visible
                        ELSE 'visible_public'
                    END
                ELSE 'unvisible'
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE users SET phone_visible = CASE WHEN phone_visible = 'unvisible' THEN '0' ELSE '1' END");
        DB::statement("UPDATE users SET email_visible = CASE WHEN email_visible = 'unvisible' THEN '0' ELSE '1' END");
        DB::statement("ALTER TABLE users MODIFY COLUMN phone_visible TINYINT(1) NOT NULL DEFAULT 1");
        DB::statement("ALTER TABLE users MODIFY COLUMN email_visible TINYINT(1) NOT NULL DEFAULT 1");
    }
};

