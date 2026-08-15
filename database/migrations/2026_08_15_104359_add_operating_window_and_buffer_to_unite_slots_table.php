<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the daily operating window (day_start/day_end) and buffer/handover
 * time to unite_slots.
 *
 * NAMING NOTE: unite_prices already has its own day_start/day_end columns
 * (added in 2026_06_18_115235_add_hourly_pricing_to_unite_prices_table),
 * but those mean something entirely different — the day/night rate split
 * boundary for hourly pricing (defaults 06:00/18:00). The columns added
 * here, on unite_slots, control WHEN a booking is allowed at all for this
 * day — the outer window custom periods (unite_slot_periods, added
 * separately) must fall inside. Two tables, two unrelated concepts,
 * same column names — kept deliberately separate rather than renamed to
 * avoid disturbing the existing, working pricing columns, but documented
 * here and at each usage site so the distinction isn't lost later.
 *
 * All new columns nullable/defaulted so existing slots keep working
 * unchanged — no drops, no backfill required (requirement: migration
 * safety).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unite_slots', function (Blueprint $table) {
            $table->time('day_start')->nullable()->after('day_of_week')
                ->comment('Daily operating window start — booking not allowed before this time. Null = no restriction beyond individual period times.');
            $table->time('day_end')->nullable()->after('day_start')
                ->comment('Daily operating window end — booking not allowed after this time. Null = no restriction beyond individual period times.');
            $table->unsignedSmallInteger('buffer_minutes')->default(0)->after('day_end')
                ->comment('Handover/buffer time in minutes required between consecutive reservations on this slot.');
        });
    }

    public function down(): void
    {
        Schema::table('unite_slots', function (Blueprint $table) {
            $table->dropColumn(['day_start', 'day_end', 'buffer_minutes']);
        });
    }
};
