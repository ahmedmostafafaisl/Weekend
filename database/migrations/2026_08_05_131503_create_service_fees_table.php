<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A configurable fixed service fee, applicable per payment category. 'key'
 * ties each row to an actual payment code path (see ServiceFee::feeFor()) —
 * this is deliberately not a free-form list an admin can add arbitrary new
 * keys to, since a new category needs corresponding application logic to
 * ever actually charge it; the admin UI only edits amount/is_active on the
 * existing seeded rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_fees', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label_en');
            $table->string('label_ar');
            $table->decimal('amount', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_fees');
    }
};
