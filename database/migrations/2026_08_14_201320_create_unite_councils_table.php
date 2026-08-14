<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A lounge (or any venue) can have multiple councils — e.g. a men's
 * sitting area and a women's sitting area, each potentially of a
 * different type. Previously this was a single flat 'council_type'
 * string on unite_details, shared across however many councils
 * council_number said existed — meaning every council had to be the
 * same type, and there was nowhere to actually describe each one
 * individually.
 *
 * council / council_number stay on unite_details as summary fields
 * (whether this venue has councils at all, and how many) — this table
 * holds the individual entries, matching the exact same one-row-per-item
 * pattern already established for unite_features/unite_offers rather
 * than introducing a new convention.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unite_councils', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unite_id');
            $table->string('type')->nullable();
            $table->timestamps();

            $table->foreign('unite_id')->references('id')->on('unites')->onDelete('cascade');
            $table->index('unite_id');
        });

        // Back-fill: any unite that already had council_type set gets that
        // value copied into council_number rows here, so existing lounges
        // don't silently lose their council type once the application
        // switches to reading from this table instead of the old flat
        // column. council_type itself is left in place on unite_details
        // (not dropped) — no destructive change to existing data.
        $existing = DB::table('unite_details')
            ->whereNotNull('council_type')
            ->where('council_type', '!=', '')
            ->get(['unite_id', 'council_number', 'council_type']);

        $now = now();

        foreach ($existing as $detail) {
            $count = max((int) ($detail->council_number ?? 1), 1);

            $rows = array_fill(0, $count, [
                'unite_id' => $detail->unite_id,
                'type' => $detail->council_type,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('unite_councils')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('unite_councils');
    }
};
