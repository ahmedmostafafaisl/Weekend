<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unite_prices', function (Blueprint $table) {
            $table->boolean('hourly_enabled')->default(false)->after('full_price')
                ->comment('Allow hourly bookings on this day type');
            $table->decimal('day_hour_price', 10, 2)->nullable()->after('hourly_enabled')
                ->comment('Per-hour rate during daytime window');
            $table->decimal('night_hour_price', 10, 2)->nullable()->after('day_hour_price')
                ->comment('Per-hour rate outside daytime window (defaults to day_hour_price)');
            $table->time('day_start')->default('06:00')->after('night_hour_price')
                ->comment('Start of daytime window');
            $table->time('day_end')->default('18:00')->after('day_start')
                ->comment('End of daytime window');
            $table->unsignedSmallInteger('min_booking_minutes')->default(60)->after('day_end')
                ->comment('Minimum booking duration in minutes');
        });
    }

    public function down(): void
    {
        Schema::table('unite_prices', function (Blueprint $table) {
            $table->dropColumn(['hourly_enabled', 'day_hour_price', 'night_hour_price',
                'day_start', 'day_end', 'min_booking_minutes']);
        });
    }
};
