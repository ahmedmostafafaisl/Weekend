<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        \DB::statement("ALTER TABLE unite_reservations MODIFY period_type ENUM('morning','evening','full_day','custom','hourly') NULL");
    }

    public function down(): void
    {
        \DB::statement("ALTER TABLE unite_reservations MODIFY period_type ENUM('morning','evening','full_day','custom') NULL");
    }
};
