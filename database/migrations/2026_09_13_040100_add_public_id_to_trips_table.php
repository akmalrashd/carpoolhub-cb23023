<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Route-facing identifier for /trips/{trip}, /explore/{trip}, etc. —
     * see App\Models\Concerns\HasPublicId. Additive only: trip_id foreign
     * keys everywhere else keep referencing the untouched sequential id.
     */
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table): void {
            $table->string('public_id', 26)->nullable()->unique()->after('id');
        });

        $existing = DB::table('trips')->whereNull('public_id')->orderBy('id')->get(['id']);
        foreach ($existing as $trip) {
            do {
                $candidate = Str::random(26);
            } while (DB::table('trips')->where('public_id', $candidate)->exists());

            DB::table('trips')->where('id', $trip->id)->update(['public_id' => $candidate]);
        }
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table): void {
            $table->dropColumn('public_id');
        });
    }
};
