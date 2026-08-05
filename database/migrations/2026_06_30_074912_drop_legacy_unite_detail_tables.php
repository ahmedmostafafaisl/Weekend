<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drops hall_details, stadium_details, lounge_details, camp_details now
 * that their data has been copied into unite_details by the previous
 * migration. Kept as a separate migration (rather than folded into the
 * data-copy migration) so a deploy can pause between copying and dropping
 * to manually verify row counts match before the old tables are gone.
 *
 * down() recreates the original 4 tables' schema (structure only — data
 * recovery relies on the previous migration's down() having been run
 * first, in the correct reverse order).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('hall_details');
        Schema::dropIfExists('stadium_details');
        Schema::dropIfExists('lounge_details');
        Schema::dropIfExists('camp_details');
    }

    public function down(): void
    {
        Schema::create('hall_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unite_id')->constrained('unites')->cascadeOnDelete();
            $table->unsignedInteger('max_chairs')->nullable();
            $table->unsignedInteger('max_tables')->nullable();
            $table->unsignedInteger('max_capacity')->nullable();
            $table->boolean('women_seating')->nullable();
            $table->boolean('kusha')->nullable();
            $table->unsignedInteger('women_seating_capacity')->nullable();
            $table->text('women_seating_details')->nullable();
            $table->boolean('women_buffet')->nullable();
            $table->text('women_buffet_details')->nullable();
            $table->unsignedInteger('women_tables_count')->nullable();
            $table->unsignedInteger('women_chairs_count')->nullable();
            $table->boolean('men_seating_available')->nullable();
            $table->unsignedInteger('men_seating_capacity')->nullable();
            $table->text('men_seating_details')->nullable();
            $table->unsignedInteger('men_tables_count')->nullable();
            $table->unsignedInteger('men_chairs_count')->nullable();
            $table->boolean('men_buffet')->nullable();
            $table->text('men_buffet_details')->nullable();
            $table->boolean('buffet')->nullable();
            $table->text('buffet_details')->nullable();
            $table->time('morning_start_time')->nullable();
            $table->time('morning_end_time')->nullable();
            $table->time('evening_start_time')->nullable();
            $table->time('evening_end_time')->nullable();
            $table->time('full_day_start_time')->nullable();
            $table->time('full_day_end_time')->nullable();
            $table->timestamps();
        });

        Schema::create('stadium_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unite_id')->constrained('unites')->cascadeOnDelete();
            $table->string('customize_Category')->nullable();
            $table->string('customize_Place')->nullable();
            $table->string('width')->nullable();
            $table->string('length')->nullable();
            $table->boolean('amenities')->nullable();
            $table->boolean('cafeteria')->nullable();
            $table->timestamps();
        });

        Schema::create('lounge_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unite_id')->constrained('unites')->cascadeOnDelete();
            $table->decimal('area', 10, 2)->nullable();
            $table->string('customize_Place')->nullable();
            $table->boolean('bedroom')->nullable();
            $table->unsignedInteger('bedroom_number')->nullable();
            $table->unsignedInteger('single_bed')->nullable();
            $table->unsignedInteger('big_bed')->nullable();
            $table->boolean('bathroom')->nullable();
            $table->unsignedInteger('bathroom_number')->nullable();
            $table->boolean('kitchen')->nullable();
            $table->boolean('pool')->nullable();
            $table->boolean('council')->nullable();
            $table->unsignedInteger('council_number')->nullable();
            $table->string('council_type')->nullable();
            $table->time('morning_start_time')->nullable();
            $table->time('morning_end_time')->nullable();
            $table->time('evening_start_time')->nullable();
            $table->time('evening_end_time')->nullable();
            $table->time('full_day_start_time')->nullable();
            $table->time('full_day_end_time')->nullable();
            $table->timestamps();
        });

        Schema::create('camp_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unite_id')->constrained('unites')->cascadeOnDelete();
            $table->string('width')->nullable();
            $table->string('length')->nullable();
            $table->unsignedInteger('seating_capacity')->nullable();
            $table->boolean('television')->nullable();
            $table->boolean('fireplace')->nullable();
            $table->boolean('bathroom')->nullable();
            $table->unsignedInteger('bathroom_number')->nullable();
            $table->time('morning_start_time')->nullable();
            $table->time('morning_end_time')->nullable();
            $table->time('evening_start_time')->nullable();
            $table->time('evening_end_time')->nullable();
            $table->time('full_day_start_time')->nullable();
            $table->time('full_day_end_time')->nullable();
            $table->timestamps();
        });
    }
};
