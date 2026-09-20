<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_settings', function (Blueprint $table) {
            $table->id();
            $table->string('hero_badge')->nullable();
            $table->string('hero_title')->nullable();
            $table->text('hero_subtitle')->nullable();
            $table->string('search_title')->nullable();
            $table->string('featured_title')->nullable();
            $table->text('featured_subtitle')->nullable();
            $table->json('featured_unite_ids')->nullable();
            $table->unsignedInteger('featured_limit')->default(8);
            $table->boolean('show_categories')->default(true);
            $table->boolean('show_featured')->default(true);
            $table->boolean('show_stats')->default(true);
            $table->boolean('show_why_us')->default(true);
            $table->boolean('show_app_section')->default(true);
            $table->string('app_title')->nullable();
            $table->text('app_text')->nullable();
            $table->string('google_play_url', 1000)->nullable();
            $table->string('apple_store_url', 1000)->nullable();
            $table->text('footer_text')->nullable();
            $table->timestamps();
        });

        Schema::create('home_slides', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('subtitle')->nullable();
            $table->string('image');
            $table->string('button_text')->nullable();
            $table->string('button_url', 1000)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_slides');
        Schema::dropIfExists('home_settings');
    }
};
