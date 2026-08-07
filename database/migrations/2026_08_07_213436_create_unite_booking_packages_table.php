<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A genuinely new concept, distinct from the existing unite_packages table
 * (men_capacity/women_capacity/price tiers, no day/time fields at all).
 * This is the "package booking" feature: a day type, a start/end time
 * window, a flat price, and a set of included services (see
 * unite_booking_package_service below) — available as an optional add-on
 * for every venue type, not just one.
 *
 * 'day' deliberately matches the exact same enum already used for
 * unite_prices.day (week_day/thursday/friday/saturday) rather than a
 * specific day-of-week — this is the established day-TYPE convention used
 * throughout pricing everywhere else in this project, not a new one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unite_booking_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unite_id')->constrained('unites')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->enum('day', ['week_day', 'thursday', 'friday', 'saturday']);
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('price', 10, 2);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('unite_booking_package_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unite_booking_package_id')->constrained('unite_booking_packages')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unite_booking_package_service');
        Schema::dropIfExists('unite_booking_packages');
    }
};
