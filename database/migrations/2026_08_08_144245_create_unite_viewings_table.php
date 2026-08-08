<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A customer's actual booking of a viewing appointment — references
 * which predefined unite_viewing_times slot was chosen, on which
 * specific calendar date. deposit_amount/deposit_refundable are
 * snapshotted here at booking time (copied from the unite's
 * viewing_deposit_* settings as they stood at that moment) rather than
 * always read live from the unite, so a provider later changing their
 * deposit settings never retroactively alters the terms of an
 * already-booked viewing appointment.
 *
 * unite_viewing_time_id is nullable with nullOnDelete() — deliberately
 * NOT cascadeOnDelete() — matching the same convention already used for
 * unite_booking_package_id on unite_reservations. The provider's edit
 * flow deletes and recreates every viewing_times row on every save
 * regardless of whether that field changed; cascadeOnDelete() would
 * silently wipe out every existing customer's viewing booking on every
 * single unrelated edit to the venue.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unite_viewings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unite_id')->constrained('unites')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('unite_viewing_time_id')->nullable()
                ->constrained('unite_viewing_times')->nullOnDelete();
            $table->date('viewing_date');
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->boolean('deposit_required')->default(false);
            $table->decimal('deposit_amount', 10, 2)->nullable();
            $table->boolean('deposit_refundable')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unite_viewings');
    }
};
