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
        Schema::create('lounge_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unite_id')->nullable();
            $table->decimal('area', 10, 7)->nullable();
            $table->string('customize_Place')->nullable();

            $table->boolean('bedroom')->default(false)->nullable();
            $table->integer('bedroom_number')->nullable();
            $table->integer('single_bed')->nullable();
            $table->integer('big_bed')->nullable();

            $table->boolean('bathroom')->default(false)->nullable();
            $table->integer('bathroom_number')->nullable();

            $table->boolean('kitchen')->default(false)->nullable();
            $table->boolean('pool')->default(false)->nullable();
            $table->boolean('council')->default(false)->nullable();
            $table->integer('council_number')->nullable();
            $table->string('council_type')->nullable();


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
        Schema::dropIfExists('lounge_details');
    }
};
