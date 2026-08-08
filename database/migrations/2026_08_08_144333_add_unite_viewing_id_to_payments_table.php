<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links a payment to a viewing-appointment deposit, matching the exact
 * existing pattern already used for reservation_id/subscription_id —
 * separate nullable FKs per purchasable entity, not a polymorphic
 * relation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('unite_viewing_id')->nullable()
                ->after('subscription_id')
                ->constrained('unite_viewings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['unite_viewing_id']);
            $table->dropColumn('unite_viewing_id');
        });
    }
};
