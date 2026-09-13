<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Route-facing identifier for /chats/{conversation} — see
     * App\Models\Concerns\HasPublicId for why this exists and why it's
     * additive (the sequential id keeps working exactly as before for
     * every internal relationship).
     */
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table): void {
            $table->string('public_id', 26)->nullable()->unique()->after('id');
        });

        $existing = DB::table('conversations')->whereNull('public_id')->orderBy('id')->get(['id']);
        foreach ($existing as $conversation) {
            do {
                $candidate = Str::random(26);
            } while (DB::table('conversations')->where('public_id', $candidate)->exists());

            DB::table('conversations')->where('id', $conversation->id)->update(['public_id' => $candidate]);
        }
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table): void {
            $table->dropColumn('public_id');
        });
    }
};
