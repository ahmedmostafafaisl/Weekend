<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('promo_code_id')->nullable()->after('phone');
            $table->foreign('promo_code_id')->references('id')->on('promo_codes')->nullOnDelete();
            $table->decimal('discount_amount', 10, 2)->nullable()->after('promo_code_id');
            $table->decimal('original_amount', 10, 2)->nullable()->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['promo_code_id']);
            $table->dropColumn(['promo_code_id', 'discount_amount', 'original_amount']);
        });
    }
};
