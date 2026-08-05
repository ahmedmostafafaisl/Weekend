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
        Schema::create('unite_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unite_id');
            $table->enum('day', ['thursday', 'friday', 'saturday', 'week_day']);

            // For stadiums
            $table->decimal('price', 15, 2)->nullable();

            // For other types (hall, lounge, camp)
            $table->decimal('morning_price', 15, 2)->nullable();
            $table->decimal('evening_price', 15, 2)->nullable();
            $table->decimal('full_price', 15, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unite_prices');
    }
};
