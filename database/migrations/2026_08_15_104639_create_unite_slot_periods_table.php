<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional, admin-configured custom availability periods for a
 * unite_slot — e.g. 06:00-08:00, 09:00-12:00, 14:00-17:00, 18:00-23:00
 * instead of one continuous operating window.
 *
 * If a slot has no periods here, availability falls back to its own
 * day_start/day_end as one continuous window (see
 * UniteSlot::availabilityWindows()). If periods do exist, they replace
 * the continuous-window behavior entirely — the gaps between them are
 * genuinely unavailable, not just unconfigured.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unite_slot_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unite_slot_id');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', ['available', 'unavailable'])->default('available');
            $table->timestamps();

            $table->foreign('unite_slot_id')->references('id')->on('unite_slots')->onDelete('cascade');
            $table->index('unite_slot_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unite_slot_periods');
    }
};
