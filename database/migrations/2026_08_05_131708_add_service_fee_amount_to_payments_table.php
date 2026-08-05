<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks the service fee actually charged on a payment, separately from the
 * base amount — matches the existing discount_amount/original_amount
 * pattern already on this table. The fee is added AFTER any promo-code
 * discount is applied (it's a flat charge, not part of the discountable
 * price), so amount = original_amount - discount_amount + service_fee_amount.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('service_fee_amount', 10, 2)->nullable()->after('original_amount');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('service_fee_amount');
        });
    }
};
