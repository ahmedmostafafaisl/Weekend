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
        Schema::create('camp_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unite_id')->nullable();
            $table->string('width')->nullable();
            $table->string('length')->nullable();
            $table->integer('seating_capacity')->nullable();

            $table->boolean('television')->default(false);
            $table->boolean('fireplace')->default(false);
            $table->boolean('bathroom')->default(false)->nullable();
            $table->integer('bathroom_number')->nullable();

            $table->time('morning_start_time')->nullable();
            $table->time('morning_end_time')->nullable();
            $table->time('evening_start_time')->nullable();
            $table->time('evening_end_time')->nullable();
            $table->time('full_day_start_time')->nullable();
            $table->time('full_day_end_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('camp_details');
    }
};
