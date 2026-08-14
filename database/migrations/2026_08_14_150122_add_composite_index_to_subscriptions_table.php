<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a composite index matching MySubscriptionController::index()'s exact
 * query pattern: WHERE user_id = ? [AND type = ?] [AND status = ?].
 *
 * subscriptions.user_id already has a single-column index (added in the
 * 2026_06_30 migration), which already narrows to one user's rows —
 * reasonable on its own since a single user's subscription count is small.
 * This composite index is a secondary, targeted improvement layered on top
 * of the real fix (removing the N+1 query burst from
 * MySubscriptionController::index(), which was the actual cause of the
 * slow/timing-out response, not a missing index).
 *
 * A single (user_id, type, status) index covers all three query shapes via
 * MySQL's leftmost-prefix rule: (user_id) alone, (user_id, type) alone, and
 * (user_id, type, status) together — no need for three separate indexes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (! $this->indexExists('subscriptions', 'subscriptions_user_id_type_status_index')) {
                $table->index(['user_id', 'type', 'status'], 'subscriptions_user_id_type_status_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if ($this->indexExists('subscriptions', 'subscriptions_user_id_type_status_index')) {
                $table->dropIndex('subscriptions_user_id_type_status_index');
            }
        });
    }

    /**
     * MySQL-safe index existence check — avoids "duplicate key name" if
     * this migration is re-run after a partial failure. Matches the exact
     * helper pattern already established in the 2026_06_30 migration.
     */
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
};
