<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_settings', function (Blueprint $table) {
            $table->string('site_name')->nullable()->after('id');
            $table->string('logo_path')->nullable()->after('site_name');
            $table->string('logo_light_path')->nullable()->after('logo_path');
            $table->string('app_image_path')->nullable()->after('logo_light_path');
            $table->string('why_us_image_path')->nullable()->after('app_image_path');
            $table->boolean('show_search')->default(true)->after('featured_limit');
        });
    }

    public function down(): void
    {
        Schema::table('home_settings', function (Blueprint $table) {
            $table->dropColumn([
                'site_name','logo_path','logo_light_path','app_image_path','why_us_image_path','show_search',
            ]);
        });
    }
};
