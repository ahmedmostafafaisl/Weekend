<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('unite_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unite_id');
            $table->string('name')->nullable();
            $table->string('start')->nullable();
            $table->string('end')->nullable();
            $table->decimal('morning_price', 10, 2)->nullable();
            $table->decimal('evening_price', 10, 2)->nullable();
            $table->decimal('full_day_price', 10, 2)->nullable();
            $table->decimal('day_hour_price', 10, 2)->nullable();
            $table->decimal('night_hour_price', 10, 2)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unite_offers');
    }
};
