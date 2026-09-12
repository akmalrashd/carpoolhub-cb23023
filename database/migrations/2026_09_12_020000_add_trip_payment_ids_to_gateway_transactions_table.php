<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Supports a single ToyyibPay bill covering MULTIPLE trip_payments (a
     * passenger paying everything owed to one driver in one transaction —
     * cheaper than one gateway fee per trip). trip_payment_id stays the path
     * for the existing single-payment flow; this column is only populated
     * for bulk bills, as a self-contained snapshot ([{trip_payment_id,
     * amount}, ...]) rather than bare ids — trip_payments rows can be
     * hard-deleted on a trip edit (see gateway_transactions' own migration),
     * so the amount has to survive that independently of the row itself.
     */
    public function up(): void
    {
        Schema::table('gateway_transactions', function (Blueprint $table) {
            $table->json('trip_payment_ids')->nullable()->after('trip_payment_id');
        });
    }

    public function down(): void
    {
        Schema::table('gateway_transactions', function (Blueprint $table) {
            $table->dropColumn('trip_payment_ids');
        });
    }
};
