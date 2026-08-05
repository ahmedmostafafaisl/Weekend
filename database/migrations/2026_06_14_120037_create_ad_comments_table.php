<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ad_id');
            $table->foreign('ad_id')->references('id')->on('ads')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_visible')->default(true)
                ->comment('Ad owner can set false to hide a comment');
            $table->timestamps();

            $table->index(['ad_id', 'is_visible']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_comments');
    }
};
