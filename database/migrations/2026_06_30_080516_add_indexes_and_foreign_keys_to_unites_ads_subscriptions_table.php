<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Applies the same fix pattern just used on unite_reservations to three more
 * tables that have the identical gap: zero indexes, zero foreign keys on
 * their primary lookup columns.
 *
 *   unites.department_id        — every venue listing/search/admin-scope query
 *   ads.user_id                 — GET /user/ads, every "my ads" query
 *   subscriptions.user_id       — GET /api/my-subscriptions, every subscription check
 *   subscriptions.package_id    — indexed only, NOT a foreign key (see below)
 *
 * Why subscriptions.package_id has no foreign key: it's polymorphic — when
 * type='property' it points at property_packages.id, when type='ad' it
 * points at ad_packages.id. A single FK constraint can't target two tables,
 * so this column gets an index for query speed but intentionally no FK.
 * (See Subscription::resolvedPackage() which already handles this split.)
 *
 * Safety: before adding any foreign key we null-out or delete orphaned rows,
 * exactly as in the unite_reservations migration, so the ALTER TABLE cannot
 * fail on pre-existing data.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->fixUnites();
        $this->fixAds();
        $this->fixSubscriptions();
    }

    public function down(): void
    {
        Schema::table('unites', function (Blueprint $table) {
            if ($this->hasForeignKey('unites', 'unites_department_id_foreign')) {
                $table->dropForeign(['department_id']);
            }
            if ($this->indexExists('unites', 'unites_department_id_index')) {
                $table->dropIndex(['department_id']);
            }
        });

        Schema::table('ads', function (Blueprint $table) {
            if ($this->hasForeignKey('ads', 'ads_user_id_foreign')) {
                $table->dropForeign(['user_id']);
            }
            if ($this->indexExists('ads', 'ads_user_id_index')) {
                $table->dropIndex(['user_id']);
            }
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            if ($this->hasForeignKey('subscriptions', 'subscriptions_user_id_foreign')) {
                $table->dropForeign(['user_id']);
            }
            if ($this->indexExists('subscriptions', 'subscriptions_user_id_index')) {
                $table->dropIndex(['user_id']);
            }
            if ($this->indexExists('subscriptions', 'subscriptions_package_id_index')) {
                $table->dropIndex(['package_id']);
            }
        });
    }

    // ── unites ───────────────────────────────────────────────────────────────

    private function fixUnites(): void
    {
        // Orphan cleanup: a unite pointing at a deleted department.
        // department_id is NOT NULL in the original schema, and a venue
        // cannot meaningfully exist without an owning department — delete
        // orphaned rows rather than trying to null a non-nullable column.
        DB::table('unites')
            ->whereNotIn('department_id', function ($q) {
                $q->select('id')->from('departments');
            })
            ->delete();

        Schema::table('unites', function (Blueprint $table) {
            if (! $this->indexExists('unites', 'unites_department_id_index')) {
                $table->index('department_id');
            }
        });

        if (! $this->hasForeignKey('unites', 'unites_department_id_foreign')) {
            Schema::table('unites', function (Blueprint $table) {
                // A department being deleted should delete its venues —
                // a venue cannot exist without an owning provider department.
                $table->foreign('department_id')
                    ->references('id')->on('departments')
                    ->onDelete('cascade');
            });
        }
    }

    // ── ads ──────────────────────────────────────────────────────────────────

    private function fixAds(): void
    {
        // ads.user_id is NOT NULL — same logic as unites.department_id above,
        // an ad cannot exist without its owning user, so orphans are deleted.
        DB::table('ads')
            ->whereNotIn('user_id', function ($q) {
                $q->select('id')->from('users');
            })
            ->delete();

        Schema::table('ads', function (Blueprint $table) {
            if (! $this->indexExists('ads', 'ads_user_id_index')) {
                $table->index('user_id');
            }
        });

        if (! $this->hasForeignKey('ads', 'ads_user_id_foreign')) {
            Schema::table('ads', function (Blueprint $table) {
                // Deleting a user should delete their ads — an ad with no
                // owner has no purpose and shouldn't linger in listings.
                $table->foreign('user_id')
                    ->references('id')->on('users')
                    ->onDelete('cascade');
            });
        }
    }

    // ── subscriptions ────────────────────────────────────────────────────────

    private function fixSubscriptions(): void
    {
        // subscriptions.user_id is NOT NULL — a subscription with no owner
        // is meaningless, so orphans are deleted (matches the payment
        // pattern: payments.user_id is nullable and set-null on delete, but
        // subscriptions were never given that nullable treatment, so we
        // follow the schema as it exists rather than changing its nullability
        // in this migration).
        DB::table('subscriptions')
            ->whereNotIn('user_id', function ($q) {
                $q->select('id')->from('users');
            })
            ->delete();

        Schema::table('subscriptions', function (Blueprint $table) {
            if (! $this->indexExists('subscriptions', 'subscriptions_user_id_index')) {
                $table->index('user_id');
            }
            // Index only — package_id is polymorphic (property_packages OR
            // ad_packages depending on `type`), so no single FK target exists.
            if (! $this->indexExists('subscriptions', 'subscriptions_package_id_index')) {
                $table->index('package_id');
            }
        });

        if (! $this->hasForeignKey('subscriptions', 'subscriptions_user_id_foreign')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                // Deleting a user should delete their subscriptions — a
                // subscription with no owner cannot be billed or renewed.
                $table->foreign('user_id')
                    ->references('id')->on('users')
                    ->onDelete('cascade');
            });
        }
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    /**
     * MySQL-safe index existence check — avoids "duplicate key name" if this
     * migration is re-run after a partial failure.
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

    /**
     * MySQL-safe foreign key existence check — avoids "duplicate foreign
     * key constraint name" if this migration is re-run after a partial
     * failure.
     */
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
