<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A genuinely new concept, distinct from the existing unite_packages table
 * (men_capacity/women_capacity/price tiers, no day/time fields at all).
 * This is the "package booking" feature, offering two genuinely different
 * booking modes per package:
 *
 *   'hours' — a package booked within a single day, at a specific time
 *             window. Uses day/start_time/end_time — 'day' matches the
 *             exact same enum already used for unite_prices.day
 *             (week_day/thursday/friday/saturday).
 *
 *   'days'  — a package booked across one or more consecutive calendar
 *             days (e.g. "Saturday" alone, or "Sunday through Friday").
 *             Uses day_from/day_to (specific days-of-week, not the 4-value
 *             category used by 'hours') plus a computed duration_days, so
 *             an actual booking can be created starting on any calendar
 *             date that falls on day_from's weekday and spanning forward
 *             duration_days nights — see UniteBookingPackage for how
 *             day_from/day_to combine into duration_days, including
 *             wraparound (e.g. friday -> sunday spans 3 days, not a
 *             negative range).
 *
 * services is a JSON array of plain text strings (e.g. ["Daily cleaning",
 * "Free breakfast"]) rather than a relation to the services table — this
 * is deliberately free text a provider types in per package, not tied to
 * the structured service catalog used elsewhere in this project.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unite_booking_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unite_id')->constrained('unites')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->enum('booking_type', ['hours', 'days']);

            // 'hours' mode
            $table->enum('day', ['week_day', 'thursday', 'friday', 'saturday'])->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            // 'days' mode
            $table->enum('day_from', ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'])->nullable();
            $table->enum('day_to', ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'])->nullable();
            $table->unsignedTinyInteger('duration_days')->nullable();

            $table->decimal('price', 10, 2);
            $table->json('services')->nullable(); // free-text list, not a relation
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unite_booking_packages');
    }
};
