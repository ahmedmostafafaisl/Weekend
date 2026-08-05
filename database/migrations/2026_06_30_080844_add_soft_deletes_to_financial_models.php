<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds deleted_at to the three financial/legal-record tables that most need
 * an audit trail and accidental-delete protection: bookings, payments, and
 * provider payouts. Hard deletes on these tables currently mean an admin
 * mistake is unrecoverable and there's no record of what was removed or when.
 *
 * No application code changes are required after this migration —
 * UniteReservation::findOrFail($id)->delete() and Payment::findOrFail($id)
 * ->delete() (the two existing hard-delete call sites in the codebase)
 * automatically become soft deletes once the SoftDeletes trait is added to
 * each model. The column is added first here; the trait is added in a
 * separate commit to each model file.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unite_reservations', function (Blueprint $table) {
            if (! Schema::hasColumn('unite_reservations', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('provider_transfers', function (Blueprint $table) {
            if (! Schema::hasColumn('provider_transfers', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('unite_reservations', function (Blueprint $table) {
            if (Schema::hasColumn('unite_reservations', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('provider_transfers', function (Blueprint $table) {
            if (Schema::hasColumn('provider_transfers', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
