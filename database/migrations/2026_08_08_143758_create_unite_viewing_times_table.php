<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Predefined, recurring weekly viewing slots a provider configures for a
 * venue — genuinely separate from unite_slots (which govern actual
 * bookings) and from unite_booking_packages. A customer picks one of
 * these predefined windows when scheduling a viewing appointment, they
 * don't pick an arbitrary time.
 *
 * Multiple rows per day are explicitly supported — e.g. Saturday
 * 09:00-11:30 AND Saturday 15:00-17:00 as two separate rows, matching the
 * requested example exactly, since a single start/end pair per day
 * couldn't represent that.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unite_viewing_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unite_id')->constrained('unites')->cascadeOnDelete();
            $table->enum('day_of_week', ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']);
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unite_viewing_times');
    }
};
