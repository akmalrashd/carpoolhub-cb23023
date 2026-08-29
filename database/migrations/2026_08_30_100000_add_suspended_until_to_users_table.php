<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Suspension was permanent-only — is_active=false stayed false until an
     * admin manually reactivated. Null here means exactly that (permanent, or
     * not suspended at all); a timestamp means the ReactivateExpiredSuspensions
     * scheduled command should flip is_active back on once it passes. Cleared
     * alongside deactivation_reason on any manual reactivation, same as today.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('suspended_until')->nullable()->after('deactivation_reason');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('suspended_until');
        });
    }
};
