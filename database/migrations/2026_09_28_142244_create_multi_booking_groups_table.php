<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Defensive duplicate of 2026_09_28_000001_create_multi_booking_groups_table.
 *
 * Both this file and the 000001 migration create the same table. On a fresh
 * install, 000001 runs first (lower sort order) and creates everything. This
 * migration adds hasTable/hasColumn guards so it is safe to run after 000001
 * without throwing "Table already exists".
 *
 * On the production server where only this file was deployed (without 000001),
 * it creates the table from scratch as originally written.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('multi_booking_groups')) {
            Schema::create('multi_booking_groups', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('department_id')->constrained()->cascadeOnDelete();
                $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
                $table->date('reservation_date');
                $table->date('end_date')->nullable();
                $table->string('period_type');
                $table->time('from_time')->nullable();
                $table->time('to_time')->nullable();
                $table->decimal('total_price', 10, 2)->default(0);
                $table->decimal('total_amount', 10, 2)->default(0);
                $table->string('status')->default('pending');
                $table->string('payment_url')->nullable();
                $table->timestamps();
            });
        }

        // Add multi_booking_group_id to unite_reservations if not yet present
        if (Schema::hasTable('unite_reservations')
            && ! Schema::hasColumn('unite_reservations', 'multi_booking_group_id')) {
            Schema::table('unite_reservations', function (Blueprint $table) {
                $table->foreignId('multi_booking_group_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('multi_booking_groups')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('unite_reservations', 'multi_booking_group_id')) {
            Schema::table('unite_reservations', function (Blueprint $table) {
                $table->dropForeignIdFor(\App\Models\MultiBookingGroup::class);
                $table->dropColumn('multi_booking_group_id');
            });
        }
        Schema::dropIfExists('multi_booking_groups');
    }
};
