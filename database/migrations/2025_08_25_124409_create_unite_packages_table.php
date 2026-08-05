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
        Schema::create('unite_packages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unite_id')->nullable();
            $table->string('name')->nullable();
            $table->integer('men_capacity')->nullable();
            $table->integer('women_capacity')->nullable();
            $table->decimal('price', 10, 2)->nullable();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unite_packages');
    }
};
