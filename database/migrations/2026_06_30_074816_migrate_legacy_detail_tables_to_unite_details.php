<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Copies every existing row from hall_details, stadium_details,
 * lounge_details, camp_details into the new unite_details table.
 * Runs AFTER unite_details exists, BEFORE the old tables are dropped —
 * so this is the safe data-preserving step in the migration path.
 *
 * down() reverses by copying rows back out per-type, in case a rollback
 * is needed before the old tables are dropped in the next migration.
 */
return new class extends Migration
{
    private array $hallColumns = [
        'max_chairs', 'max_tables', 'max_capacity', 'women_seating', 'kusha',
        'women_seating_capacity', 'women_seating_details', 'women_buffet', 'women_buffet_details',
        'women_tables_count', 'women_chairs_count', 'men_seating_available', 'men_seating_capacity',
        'men_seating_details', 'men_tables_count', 'men_chairs_count', 'men_buffet', 'men_buffet_details',
        'buffet', 'buffet_details', 'morning_start_time', 'morning_end_time',
        'evening_start_time', 'evening_end_time', 'full_day_start_time', 'full_day_end_time',
    ];

    private array $stadiumColumns = [
        'customize_Category', 'customize_Place', 'width', 'length', 'amenities', 'cafeteria',
    ];

    private array $loungeColumns = [
        'area', 'customize_Place', 'bedroom', 'bedroom_number', 'single_bed', 'big_bed',
        'bathroom', 'bathroom_number', 'kitchen', 'pool', 'council', 'council_number', 'council_type',
        'morning_start_time', 'morning_end_time', 'evening_start_time', 'evening_end_time',
        'full_day_start_time', 'full_day_end_time',
    ];

    private array $campColumns = [
        'width', 'length', 'seating_capacity', 'television', 'fireplace',
        'bathroom', 'bathroom_number', 'morning_start_time', 'morning_end_time',
        'evening_start_time', 'evening_end_time', 'full_day_start_time', 'full_day_end_time',
    ];

    public function up(): void
    {
        $this->copyTable('hall_details', $this->hallColumns);
        $this->copyTable('stadium_details', $this->stadiumColumns);
        $this->copyTable('lounge_details', $this->loungeColumns);
        $this->copyTable('camp_details', $this->campColumns);
    }

    public function down(): void
    {
        // Reversal: push unite_details rows back into their original table,
        // scoped by the unite's type (only relevant if the old tables still exist).
        if (! Schema::hasTable('unite_details')) {
            return;
        }

        $this->restoreTable('hall_details', 'hall', $this->hallColumns);
        $this->restoreTable('stadium_details', 'stadium', $this->stadiumColumns);
        $this->restoreTable('lounge_details', 'lounge', $this->loungeColumns);
        $this->restoreTable('camp_details', 'camp', $this->campColumns);
    }

    private function copyTable(string $legacyTable, array $columns): void
    {
        if (! Schema::hasTable($legacyTable)) {
            return; // already migrated / table never existed on this environment
        }

        DB::table($legacyTable)->orderBy('id')->chunk(200, function ($rows) use ($columns) {
            foreach ($rows as $row) {
                $data = ['unite_id' => $row->unite_id];

                foreach ($columns as $col) {
                    $data[$col] = $row->{$col} ?? null;
                }

                $data['created_at'] = $row->created_at ?? now();
                $data['updated_at'] = $row->updated_at ?? now();

                // updateOrCreate semantics via upsert — safe to re-run this
                // migration if it's interrupted partway through.
                DB::table('unite_details')->updateOrInsert(
                    ['unite_id' => $data['unite_id']],
                    $data
                );
            }
        });
    }

    private function restoreTable(string $legacyTable, string $uniteType, array $columns): void
    {
        if (! Schema::hasTable($legacyTable)) {
            return;
        }

        $uniteIds = DB::table('unites')->where('type', $uniteType)->pluck('id');

        DB::table('unite_details')
            ->whereIn('unite_id', $uniteIds)
            ->orderBy('id')
            ->chunk(200, function ($rows) use ($legacyTable, $columns) {
                foreach ($rows as $row) {
                    $data = ['unite_id' => $row->unite_id];
                    foreach ($columns as $col) {
                        $data[$col] = $row->{$col} ?? null;
                    }
                    $data['created_at'] = $row->created_at;
                    $data['updated_at'] = $row->updated_at;

                    DB::table($legacyTable)->updateOrInsert(
                        ['unite_id' => $data['unite_id']],
                        $data
                    );
                }
            });
    }
};
