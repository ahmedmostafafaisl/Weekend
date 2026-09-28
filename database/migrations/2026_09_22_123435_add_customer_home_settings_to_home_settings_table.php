<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the customer/guest home screen configuration columns.
     *
     *   featured_department_ids  — JSON array of department ids selected
     *                              by the admin. Up to 10 shown to guests
     *                              and customers on the home API.
     *   price_min                — Lower bound of the unite price filter
     *                              slider exposed to customers (SAR).
     *   price_max                — Upper bound of the price filter slider.
     *
     * Defensive: hasColumn() guards each add so the migration is safe to
     * re-run on any server.
     */
    public function up(): void
    {
        Schema::table('home_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('home_settings', 'featured_department_ids')) {
                $table->json('featured_department_ids')->nullable()->after('featured_unite_ids');
            }
            if (! Schema::hasColumn('home_settings', 'price_min')) {
                $table->unsignedInteger('price_min')->default(0)->after('featured_department_ids');
            }
            if (! Schema::hasColumn('home_settings', 'price_max')) {
                $table->unsignedInteger('price_max')->default(10000)->after('price_min');
            }
        });
    }

    public function down(): void
    {
        Schema::table('home_settings', function (Blueprint $table) {
            foreach (['featured_department_ids', 'price_min', 'price_max'] as $col) {
                if (Schema::hasColumn('home_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
