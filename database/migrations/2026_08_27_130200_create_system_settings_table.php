<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Seeded with FuelPriceService::FALLBACK's current values exactly, so
        // nothing changes on day one — this is a new editable admin override,
        // not a replacement default.
        $now = now();
        DB::table('system_settings')->insert([
            ['key' => 'fuel_price_ron95_budi', 'value' => '1.99', 'updated_by' => null, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'fuel_price_ron95_market', 'value' => '3.77', 'updated_by' => null, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'fuel_price_ron97_market', 'value' => '4.25', 'updated_by' => null, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'fuel_price_diesel_market', 'value' => '4.67', 'updated_by' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
