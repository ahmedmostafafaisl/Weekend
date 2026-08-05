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
        Schema::create('unite_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unite_id');
            $table->time('morning_start')->nullable();
            $table->time('morning_end')->nullable();
            $table->time('evening_start')->nullable();
            $table->time('evening_end')->nullable();
            $table->time('full_start')->nullable();
            $table->time('full_end')->nullable();

            $table->enum('status', ['available', 'booked', 'unavailable'])->default('available');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unite_slots');
    }
};
