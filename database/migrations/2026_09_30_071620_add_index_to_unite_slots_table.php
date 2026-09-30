<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Clean orphaned rows so the FK constraint can be added safely
        DB::table('unite_slots')
            ->whereNotIn('unite_id', function ($q) {
                $q->select('id')->from('unites');
            })
            ->delete();

        Schema::table('unite_slots', function (Blueprint $table) {
            // Composite index — covers both (unite_id) and (unite_id, day_of_week)
            // queries via MySQL's leftmost-prefix rule.
            if (! $this->indexExists('unite_slots', 'unite_slots_unite_id_day_of_week_index')) {
                $table->index(['unite_id', 'day_of_week'], 'unite_slots_unite_id_day_of_week_index');
            }
        });

        if (! $this->hasForeignKey('unite_slots', 'unite_slots_unite_id_foreign')) {
            Schema::table('unite_slots', function (Blueprint $table) {
                $table->foreign('unite_id')
                    ->references('id')->on('unites')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::table('unite_slots', function (Blueprint $table) {
            if ($this->hasForeignKey('unite_slots', 'unite_slots_unite_id_foreign')) {
                $table->dropForeign(['unite_id']);
            }
            if ($this->indexExists('unite_slots', 'unite_slots_unite_id_day_of_week_index')) {
                $table->dropIndex('unite_slots_unite_id_day_of_week_index');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $result = Schema::getConnection()->select(
            'SELECT COUNT(1) as cnt FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [Schema::getConnection()->getDatabaseName(), $table, $indexName]
        );

        return ($result[0]->cnt ?? 0) > 0;
    }

    private function hasForeignKey(string $table, string $constraintName): bool
    {
        $result = Schema::getConnection()->select(
            "SELECT COUNT(1) as cnt FROM information_schema.table_constraints
             WHERE table_schema = ? AND table_name = ? AND constraint_name = ?
             AND constraint_type = 'FOREIGN KEY'",
            [Schema::getConnection()->getDatabaseName(), $table, $constraintName]
        );

        return ($result[0]->cnt ?? 0) > 0;
    }
};
