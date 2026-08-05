<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorite_unites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('unite_id')->constrained('unites')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'unite_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorite_unites');
    }
};
