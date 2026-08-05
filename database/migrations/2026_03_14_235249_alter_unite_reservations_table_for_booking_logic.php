<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unite_reservations', function (Blueprint $table) {
            $table->date('reservation_date')->nullable()->after('user_id');
            $table->enum('period_type', ['morning', 'evening', 'full_day', 'custom'])->nullable()->after('reservation_date');
            $table->time('from_time')->nullable()->after('period_type');
            $table->time('to_time')->nullable()->after('from_time');
            $table->decimal('price', 15, 2)->nullable()->after('to_time');

            $table->dropColumn([
                'start',
                'end',
                'morning_price',
                'evening_price',
                'full_day_price',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('unite_reservations', function (Blueprint $table) {
            $table->string('start')->nullable();
            $table->string('end')->nullable();
            $table->decimal('morning_price', 10, 2)->nullable();
            $table->decimal('evening_price', 10, 2)->nullable();
            $table->decimal('full_day_price', 10, 2)->nullable();

            $table->dropColumn([
                'reservation_date',
                'period_type',
                'from_time',
                'to_time',
                'price',
            ]);
        });
    }
};
