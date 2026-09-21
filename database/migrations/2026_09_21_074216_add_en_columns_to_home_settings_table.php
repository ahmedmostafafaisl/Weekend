<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('home_settings', 'hero_badge_en')) {
                $table->string('hero_badge_en')->nullable()->after('hero_badge');
            }
            if (! Schema::hasColumn('home_settings', 'hero_title_en')) {
                $table->string('hero_title_en')->nullable()->after('hero_title');
            }
            if (! Schema::hasColumn('home_settings', 'hero_subtitle_en')) {
                $table->text('hero_subtitle_en')->nullable()->after('hero_subtitle');
            }
            if (! Schema::hasColumn('home_settings', 'search_title_en')) {
                $table->string('search_title_en')->nullable()->after('search_title');
            }
            if (! Schema::hasColumn('home_settings', 'featured_title_en')) {
                $table->string('featured_title_en')->nullable()->after('featured_title');
            }
            if (! Schema::hasColumn('home_settings', 'featured_subtitle_en')) {
                $table->text('featured_subtitle_en')->nullable()->after('featured_subtitle');
            }
            if (! Schema::hasColumn('home_settings', 'app_title_en')) {
                $table->string('app_title_en')->nullable()->after('app_title');
            }
            if (! Schema::hasColumn('home_settings', 'app_text_en')) {
                $table->text('app_text_en')->nullable()->after('app_text');
            }
            if (! Schema::hasColumn('home_settings', 'footer_text_en')) {
                $table->text('footer_text_en')->nullable()->after('footer_text');
            }
        });
    }

    public function down(): void
    {
        $cols = [
            'hero_badge_en', 'hero_title_en', 'hero_subtitle_en',
            'search_title_en', 'featured_title_en', 'featured_subtitle_en',
            'app_title_en', 'app_text_en', 'footer_text_en',
        ];

        Schema::table('home_settings', function (Blueprint $table) use ($cols) {
            $existing = array_filter($cols, fn ($c) => Schema::hasColumn('home_settings', $c));
            if ($existing) {
                $table->dropColumn(array_values($existing));
            }
        });
    }
};
