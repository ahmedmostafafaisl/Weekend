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
        Schema::create('unite_reservations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unite_id');
            $table->unsignedBigInteger('user_id')->nullable(); // Assuming you want to track the user making the reservation
            $table->string('start');
            $table->string('end');
            $table->decimal('morning_price', 10, 2)->nullable();
            $table->decimal('evening_price', 10, 2)->nullable();
            $table->decimal('full_day_price', 10, 2)->nullable();
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unite_reservations');
    }
};
