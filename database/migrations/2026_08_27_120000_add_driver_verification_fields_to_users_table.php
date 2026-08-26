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
            $table->enum('driver_verification_status', ['pending', 'approved', 'rejected'])
                ->nullable()
                ->after('is_active');
            $table->text('driver_verification_reason')->nullable()->after('driver_verification_status');
            $table->timestamp('driver_verified_at')->nullable()->after('driver_verification_reason');
            $table->foreignId('driver_reviewed_by')->nullable()->after('driver_verified_at')
                ->constrained('users')->nullOnDelete();
            $table->date('driving_license_expiry')->nullable()->after('selfie_photo');
        });

        // Backfill existing drivers so nobody gets mislabeled: is_active=true is
        // treated as already approved, is_active=false as still pending — this
        // matches exactly what the admin queue query already assumed before
        // this migration (User::where('role','driver')->where('is_active', false)).
        DB::table('users')->where('role', 'driver')->where('is_active', true)->update([
            'driver_verification_status' => 'approved',
            'driver_verified_at' => DB::raw('updated_at'),
        ]);

        DB::table('users')->where('role', 'driver')->where('is_active', false)->update([
            'driver_verification_status' => 'pending',
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['driver_reviewed_by']);
            $table->dropColumn([
                'driver_verification_status',
                'driver_verification_reason',
                'driver_verified_at',
                'driver_reviewed_by',
                'driving_license_expiry',
            ]);
        });
    }
};
