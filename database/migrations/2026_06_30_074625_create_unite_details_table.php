<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidates HallDetail, StadiumDetail, LoungeDetail, CampDetail into a
 * single polymorphic unite_details table.
 *
 * Why one table instead of raw JSON on `unites`: every field below was
 * previously a typed, queryable column (whereHas('hallDetail', fn($d) =>
 * $d->where('max_capacity', '>=', $cap)) is a real query in UniteRepository).
 * JSON columns lose that — MySQL JSON queries work but lose indexes, type
 * safety, and validation rule clarity. A single table keeps every existing
 * query pattern working with a one-line relation name change.
 *
 * The table is a superset union of all 4 original tables' columns. Each
 * Unite only ever populates the columns relevant to its type — the same
 * sparse-row tradeoff the 4-table design already had per type, just
 * centralized into one table instead of four.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unite_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unite_id')->unique()->constrained('unites')->cascadeOnDelete();

            // ── Hall fields ──────────────────────────────────────────────────────
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

            // ── Stadium fields ───────────────────────────────────────────────────
            $table->string('customize_Category')->nullable();
            $table->string('customize_Place')->nullable();
            $table->string('width')->nullable();
            $table->string('length')->nullable();
            $table->boolean('amenities')->nullable();
            $table->boolean('cafeteria')->nullable();

            // ── Lounge fields ────────────────────────────────────────────────────
            $table->decimal('area', 10, 2)->nullable();
            $table->boolean('bedroom')->nullable();
            $table->unsignedInteger('bedroom_number')->nullable();
            $table->unsignedInteger('single_bed')->nullable();
            $table->unsignedInteger('big_bed')->nullable();
            $table->boolean('kitchen')->nullable();
            $table->boolean('pool')->nullable();
            $table->boolean('council')->nullable();
            $table->unsignedInteger('council_number')->nullable();
            $table->string('council_type')->nullable();

            // ── Camp fields ──────────────────────────────────────────────────────
            $table->unsignedInteger('seating_capacity')->nullable();
            $table->boolean('television')->nullable();
            $table->boolean('fireplace')->nullable();

            // ── Shared between hall / lounge / camp ─────────────────────────────
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

    public function down(): void
    {
        Schema::dropIfExists('unite_details');
    }
};
