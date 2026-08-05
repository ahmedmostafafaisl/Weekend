<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();           // e.g. SUMMER20
            $table->string('description')->nullable();

            // Discount definition
            $table->enum('discount_type', ['percentage', 'fixed']); // % off or flat amount off
            $table->decimal('discount_value', 10, 2);               // 20 = 20% or 20 SAR

            // Optional constraints
            $table->decimal('min_amount', 10, 2)->nullable();        // minimum order value
            $table->decimal('max_discount', 10, 2)->nullable();      // cap for percentage discounts
            $table->integer('max_uses')->nullable();                  // null = unlimited
            $table->integer('max_uses_per_user')->nullable();        // null = unlimited per user

            // Date window
            $table->date('starts_at')->nullable();
            $table->date('expires_at')->nullable();

            $table->boolean('is_active')->default(true);

            $table->unsignedBigInteger('created_by')->nullable();    // admin who created it
            $table->foreign('created_by')->references('id')->on('admins')->nullOnDelete();

            $table->timestamps();

            $table->index('code');
            $table->index(['is_active', 'expires_at']);
        });

        Schema::create('promo_code_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('promo_code_id');
            $table->foreign('promo_code_id')->references('id')->on('promo_codes')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->foreign('payment_id')->references('id')->on('payments')->nullOnDelete();
            $table->decimal('discount_amount', 10, 2);    // actual amount discounted
            $table->decimal('original_amount', 10, 2);    // amount before discount
            $table->decimal('final_amount', 10, 2);       // amount charged
            $table->timestamps();

            $table->index(['promo_code_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_code_usages');
        Schema::dropIfExists('promo_codes');
    }
};
