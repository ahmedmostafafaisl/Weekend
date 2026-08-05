<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->string('city')->nullable()->after('description')
                ->comment('City/location this ad targets. Null = all cities.');

            $table->enum('target_audience', ['men', 'women', 'both'])->default('both')->after('city')
                ->comment('men = men only, women = women only, both = everyone.');

            $table->enum('target_user_type', ['all', 'customers', 'providers'])->default('all')->after('target_audience')
                ->comment('Which user type sees this ad.');
        });
    }

    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropColumn(['city', 'target_audience', 'target_user_type']);
        });
    }
};
