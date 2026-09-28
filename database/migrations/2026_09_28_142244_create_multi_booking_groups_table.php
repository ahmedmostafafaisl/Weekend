<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

        // Pivot: group ↔ reservation (one-to-many but explicit for clarity)
        Schema::table('unite_reservations', function (Blueprint $table) {
            $table->foreignId('multi_booking_group_id')
                ->nullable()
                ->after('id')
                ->constrained('multi_booking_groups')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('unite_reservations', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\MultiBookingGroup::class);
            $table->dropColumn('multi_booking_group_id');
        });
        Schema::dropIfExists('multi_booking_groups');
    }
};
