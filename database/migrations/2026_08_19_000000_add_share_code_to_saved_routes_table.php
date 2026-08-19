<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saved_routes', function (Blueprint $table): void {
            $table->char('share_code', 6)->nullable()->unique()->after('id');
        });

        // Backfill existing rows — they predate this column, so each needs its
        // own generated code before the column can be made required by future
        // code. Same alphabet as SavedRouteService::generateShareCode() so a
        // fresh install and this backfill produce indistinguishable codes.
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $existing = DB::table('saved_routes')->whereNull('share_code')->orderBy('id')->get(['id']);
        foreach ($existing as $route) {
            do {
                $code = '';
                for ($i = 0; $i < 6; $i++) {
                    $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
                }
            } while (DB::table('saved_routes')->where('share_code', $code)->exists());

            DB::table('saved_routes')->where('id', $route->id)->update(['share_code' => $code]);
        }
    }

    public function down(): void
    {
        Schema::table('saved_routes', function (Blueprint $table): void {
            $table->dropColumn('share_code');
        });
    }
};
