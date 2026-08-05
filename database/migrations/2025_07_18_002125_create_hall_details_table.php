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
        Schema::create('hall_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unite_id')->nullable();


            $table->integer('max_chairs')->nullable();
            $table->integer('max_tables')->nullable();
            $table->integer('max_capacity')->nullable();

            // Women Seating
            $table->boolean('women_seating')->default(false);
            $table->boolean('kusha')->default(false);
            $table->integer('women_tables_count')->nullable();
            $table->integer('women_chairs_count')->nullable();
            $table->integer('women_seating_capacity')->nullable();
            $table->text('women_seating_details')->nullable();
            $table->boolean('women_buffet')->default(false);
            $table->text('women_buffet_details')->nullable();

            // Men Seating
            $table->boolean('men_seating_available')->default(false);
            $table->integer('men_tables_count')->nullable();
            $table->integer('men_chairs_count')->nullable();
            $table->integer('men_seating_capacity')->nullable();
            $table->text('men_seating_details')->nullable();
            $table->boolean('men_buffet')->default(false);
            $table->text('men_buffet_details')->nullable();

            $table->boolean('buffet')->default(false);
            $table->text('buffet_details')->nullable();

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
        Schema::dropIfExists('hall_details');
    }
};
