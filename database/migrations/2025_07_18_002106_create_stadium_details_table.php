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
        Schema::create('stadium_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unite_id')->nullable();
            $table->string('customize_Category')->nullable();
            $table->string('customize_Place')->nullable();
            $table->string('width')->nullable();
            $table->string('length')->nullable();
            $table->boolean('amenities')->default(false)->nullable();
            $table->boolean('cafeteria')->default(false)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stadium_details');
    }
};
