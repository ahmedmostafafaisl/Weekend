<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * property_packages and ad_packages had zero indexes at all beyond the
 * primary key since their original migrations. Both are filtered by
 * status in their actual query paths — most notably
 * PropertyPackageRepository::getAllPackages(), which both tables' public,
 * unauthenticated pricing-page endpoint hits on every anonymous visit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_packages', function (Blueprint $table) {
            if (! $this->indexExists('property_packages', 'property_packages_status_index')) {
                $table->index('status');
            }
        });

        Schema::table('ad_packages', function (Blueprint $table) {
            if (! $this->indexExists('ad_packages', 'ad_packages_status_index')) {
                $table->index('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('property_packages', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('ad_packages', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
    }

    /**
     * Same MySQL-safe existence check already established in
     * 2026_06_30_072650_add_indexes_and_foreign_keys_to_unite_reservations_table.php
     * — avoids a "duplicate key name" error if this migration is re-run
     * after a partial failure.
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
