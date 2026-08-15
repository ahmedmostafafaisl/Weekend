<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * unite_prices.unite_id and unite_offers.unite_id are plain
 * unsignedBigInteger columns — no foreign key constraint at all, and
 * therefore no implicit index either (confirmed empirically earlier this
 * session, via a real MariaDB test, that MySQL/MariaDB only auto-creates
 * an index for an actual FK constraint on a column, not merely because
 * the column happens to be named *_id). Both tables are queried by
 * unite_id on essentially every request that touches a venue's pricing:
 * UniteRepository::search()'s price filter, every reservation price
 * calculation, every availability check.
 *
 * unite_prices gets an additional composite (unite_id, day), matching
 * its actual lookup pattern exactly (a specific day's price row for a
 * specific venue).
 *
 * Same safety pattern already established for unite_reservations/unites/
 * ads/subscriptions: orphaned rows (a unite_id pointing at a unite that
 * no longer exists) are deleted before the FK is added, so the ALTER
 * TABLE cannot fail on pre-existing bad data.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->fixTable('unite_prices', 'unite_prices_unite_id_day_index', ['unite_id', 'day']);
        $this->fixTable('unite_offers', 'unite_offers_unite_id_index', ['unite_id']);
    }

    private function fixTable(string $table, string $indexName, array $indexColumns): void
    {
        DB::table($table)
            ->whereNotIn('unite_id', function ($q) {
                $q->select('id')->from('unites');
            })
            ->delete();

        Schema::table($table, function (Blueprint $t) use ($table, $indexName, $indexColumns) {
            if (! $this->indexExists($table, $indexName)) {
                $t->index($indexColumns, $indexName);
            }
        });

        Schema::table($table, function (Blueprint $t) use ($table) {
            if (! $this->hasForeignKey($table, "{$table}_unite_id_foreign")) {
                $t->foreign('unite_id')->references('id')->on('unites')->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('unite_prices', function (Blueprint $table) {
            $table->dropForeign(['unite_id']);
            $table->dropIndex('unite_prices_unite_id_day_index');
        });

        Schema::table('unite_offers', function (Blueprint $table) {
            $table->dropForeign(['unite_id']);
            $table->dropIndex('unite_offers_unite_id_index');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $dbName = $connection->getDatabaseName();

        $result = $connection->select(
            'SELECT COUNT(1) as cnt FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$dbName, $table, $indexName]
        );

        return ($result[0]->cnt ?? 0) > 0;
    }

    private function hasForeignKey(string $table, string $constraintName): bool
    {
        $connection = Schema::getConnection();
        $dbName = $connection->getDatabaseName();

        $result = $connection->select(
            "SELECT COUNT(1) as cnt FROM information_schema.table_constraints
             WHERE table_schema = ? AND table_name = ? AND constraint_name = ?
             AND constraint_type = 'FOREIGN KEY'",
            [$dbName, $table, $constraintName]
        );

        return ($result[0]->cnt ?? 0) > 0;
    }
};
