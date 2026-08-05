<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unite_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unite_id')->constrained('unites')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->unique(['unite_id', 'user_id']);
            $table->unique(['unite_id', 'ip_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unite_views');
    }
};
