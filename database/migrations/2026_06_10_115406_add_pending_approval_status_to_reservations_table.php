<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL: modify enum to add pending_approval value
        DB::statement("ALTER TABLE unite_reservations MODIFY COLUMN status
            ENUM('pending', 'pending_approval', 'confirmed', 'cancelled')
            NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE unite_reservations MODIFY COLUMN status
            ENUM('pending', 'confirmed', 'cancelled')
            NOT NULL DEFAULT 'pending'");
    }
};
