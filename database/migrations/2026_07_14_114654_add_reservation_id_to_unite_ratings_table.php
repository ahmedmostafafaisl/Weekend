<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('unite_ratings', 'reservation_id')) {
            Schema::table('unite_ratings', function (Blueprint $table) {
                $table->foreignId('reservation_id')
                    ->nullable()
                    ->after('unite_id')
                    ->constrained('unite_reservations')
                    ->nullOnDelete();
            });
        }

        // BUG FIX: the composite unique(unite_id, user_id) index dropped
        // below is the ONLY index covering unite_id — MySQL uses a
        // composite index's leading column(s) to satisfy a foreign key's
        // indexing requirement, so unite_ratings_unite_id_foreign has been
        // silently relying on this exact index this whole time. Dropping
        // it without giving the FK a replacement index first fails with
        // "Cannot drop index ... needed in a foreign key constraint"
        // (MySQL error 1553). Adding a plain, explicitly-named index on
        // unite_id alone first resolves this.
        if (! $this->indexExists('unite_ratings', 'unite_ratings_unite_id_index')) {
            Schema::table('unite_ratings', function (Blueprint $table) {
                $table->index('unite_id', 'unite_ratings_unite_id_index');
            });
        }

        if ($this->indexExists('unite_ratings', 'unite_ratings_unite_id_user_id_unique')) {
            Schema::table('unite_ratings', function (Blueprint $table) {
                $table->dropUnique(['unite_id', 'user_id']);
            });
        }

        if (! $this->indexExists('unite_ratings', 'unite_ratings_reservation_id_unique')) {
            Schema::table('unite_ratings', function (Blueprint $table) {
                $table->unique('reservation_id');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('unite_ratings', 'unite_ratings_reservation_id_unique')) {
            Schema::table('unite_ratings', function (Blueprint $table) {
                $table->dropUnique(['reservation_id']);
            });
        }

        if (Schema::hasColumn('unite_ratings', 'reservation_id')) {
            Schema::table('unite_ratings', function (Blueprint $table) {
                $table->dropForeign(['reservation_id']);
                $table->dropColumn('reservation_id');
            });
        }

        if (! $this->indexExists('unite_ratings', 'unite_ratings_unite_id_user_id_unique')) {
            Schema::table('unite_ratings', function (Blueprint $table) {
                $table->unique(['unite_id', 'user_id']);
            });
        }

        // The composite unique index restored above covers unite_id again,
        // making the standalone index from up() redundant — drop it to
        // match the original schema exactly.
        if ($this->indexExists('unite_ratings', 'unite_ratings_unite_id_index')) {
            Schema::table('unite_ratings', function (Blueprint $table) {
                $table->dropIndex('unite_ratings_unite_id_index');
            });
        }
    }

    /**
     * Checks the actual database catalog for an index by name — avoids
     * depending on doctrine/dbal (not guaranteed to be installed) just to
     * ask "does this index exist yet."
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $database = DB::getDatabaseName();

        $result = DB::selectOne(
            'select count(*) as count from information_schema.statistics
             where table_schema = ? and table_name = ? and index_name = ?',
            [$database, $table, $indexName]
        );

        return $result && $result->count > 0;
    }
};
