<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * notifications' morphs('notifiable') already indexes (notifiable_type,
 * notifiable_id) -- verified against the real Laravel Blueprint source
 * before assuming this needed fixing (numericMorphs() explicitly calls
 * ->index() on that pair). What's missing is coverage for the actual hot
 * query pattern: NotificationController::unreadCount()/index() both add
 * a read_at filter on top of that pair -- ->whereNull('read_at') for the
 * unread count (hit on essentially every app open, and polled regularly
 * for the mobile badge) and the unread_only listing filter. A composite
 * including read_at lets one index satisfy the full WHERE clause instead
 * of only the notifiable_type/notifiable_id half of it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (! $this->indexExists('notifications', 'notifications_notifiable_read_at_index')) {
                $table->index(
                    ['notifiable_type', 'notifiable_id', 'read_at'],
                    'notifications_notifiable_read_at_index'
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_notifiable_read_at_index');
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
};
