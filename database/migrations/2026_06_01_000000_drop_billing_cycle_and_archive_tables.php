<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table): void {
            if (Schema::hasColumn('trips', 'billing_cycle_id')) {
                $table->dropForeign(['billing_cycle_id']);
                $table->dropColumn('billing_cycle_id');
            }
        });

        Schema::dropIfExists('archived_trip_payments');
        Schema::dropIfExists('archived_trip_participants');
        Schema::dropIfExists('archived_trips');
        Schema::dropIfExists('billing_cycles');
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table): void {
            $table->unsignedBigInteger('billing_cycle_id')->nullable()->after('parent_trip_id');
        });

        Schema::create('billing_cycles', function (Blueprint $table): void {
            $table->id();
            $table->string('month_key', 7)->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('open');
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }
};
