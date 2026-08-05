<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the indexes and foreign keys that unite_reservations has been missing
 * since its original migration. This is the highest-impact fix identified in
 * the architecture review — every booking creation, availability check, and
 * report query filters or joins on unite_id, user_id, reservation_date, or
 * status, and none of them were indexed.
 *
 * Safety: before adding the foreign keys we null-out any orphaned references
 * (a unite_id or user_id pointing at a row that no longer exists). Without
 * this step the ALTER TABLE would fail outright if any orphaned rows exist.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Step 1: clean up orphaned references so FK constraints can be added ──
        // unite_id is NOT NULL in the original schema, so orphans here are deleted
        // (a reservation can't logically exist without its venue).
        $orphanedUnites = DB::table('unite_reservations')
            ->whereNotIn('unite_id', function ($q) {
                $q->select('id')->from('unites');
            })
            ->count();

        if ($orphanedUnites > 0) {
            DB::table('unite_reservations')
                ->whereNotIn('unite_id', function ($q) {
                    $q->select('id')->from('unites');
                })
                ->delete();
        }

        // user_id is nullable, so orphans here are set to NULL instead of deleted
        // (the reservation itself is still valid — we just lose the customer link).
        DB::table('unite_reservations')
            ->whereNotNull('user_id')
            ->whereNotIn('user_id', function ($q) {
                $q->select('id')->from('users');
            })
            ->update(['user_id' => null]);

        // ── Step 2: indexes ─────────────────────────────────────────────────────
        Schema::table('unite_reservations', function (Blueprint $table) {
            if (! $this->indexExists('unite_reservations', 'unite_reservations_unite_id_index')) {
                $table->index('unite_id');
            }
            if (! $this->indexExists('unite_reservations', 'unite_reservations_user_id_index')) {
                $table->index('user_id');
            }
            if (! $this->indexExists('unite_reservations', 'unite_reservations_reservation_date_index')) {
                $table->index('reservation_date');
            }
            if (! $this->indexExists('unite_reservations', 'unite_reservations_status_index')) {
                $table->index('status');
            }
            // Composite index for the most common query pattern: checking
            // availability for a given venue + date + status (pending/confirmed).
            if (! $this->indexExists('unite_reservations', 'unite_reservations_availability_index')) {
                $table->index(
                    ['unite_id', 'reservation_date', 'status'],
                    'unite_reservations_availability_index'
                );
            }
        });

        // ── Step 3: foreign keys ────────────────────────────────────────────────
        Schema::table('unite_reservations', function (Blueprint $table) {
            // A venue being deleted should delete its reservations — a
            // reservation cannot meaningfully exist without its venue.
            $table->foreign('unite_id')
                ->references('id')->on('unites')
                ->onDelete('cascade');

            // A user being deleted should NOT delete their reservation history
            // (financial/legal record) — null the link instead, matching the
            // existing nullable user_id column and the same pattern already
            // used on payments.user_id.
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('unite_reservations', function (Blueprint $table) {
            $table->dropForeign(['unite_id']);
            $table->dropForeign(['user_id']);
            $table->dropIndex(['unite_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['reservation_date']);
            $table->dropIndex(['status']);
            $table->dropIndex('unite_reservations_availability_index');
        });
    }

    /**
     * Check whether an index already exists on a table (MySQL-safe, avoids
     * "duplicate key name" errors if this migration is re-run after a
     * partial failure).
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
