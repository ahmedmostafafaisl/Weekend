<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds 'package' as a valid period_type — matches the existing raw-SQL
 * MODIFY pattern already used when 'hourly' was added to this same enum.
 * unite_booking_package_id links a reservation back to the specific
 * package that was booked, nullable since it's only ever set for
 * period_type='package' reservations.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE unite_reservations MODIFY period_type ENUM('morning','evening','full_day','custom','hourly','package') NULL");

        Schema::table('unite_reservations', function (Blueprint $table) {
            $table->foreignId('unite_booking_package_id')->nullable()
                ->after('period_type')
                ->constrained('unite_booking_packages')->nullOnDelete();
            // Only ever set for 'days'-type package bookings, which span
            // multiple calendar days — reservation_date is the check-in
            // date, end_date the check-out date. Every other reservation
            // (including 'hours'-type packages) continues to use
            // reservation_date alone, exactly as before.
            $table->date('end_date')->nullable()->after('reservation_date');
        });
    }

    public function down(): void
    {
        Schema::table('unite_reservations', function (Blueprint $table) {
            $table->dropForeign(['unite_booking_package_id']);
            $table->dropColumn(['unite_booking_package_id', 'end_date']);
        });

        DB::statement("ALTER TABLE unite_reservations MODIFY period_type ENUM('morning','evening','full_day','custom','hourly') NULL");
    }
};
