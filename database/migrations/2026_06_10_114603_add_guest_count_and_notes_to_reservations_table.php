<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unite_reservations', function (Blueprint $table) {
            $table->unsignedInteger('guest_count')->nullable()->after('price')
                ->comment('Number of guests. Required for halls and lounges, validated against max_capacity.');
            $table->text('notes')->nullable()->after('guest_count')
                ->comment('Customer special requests, visible to provider and admin.');
        });
    }

    public function down(): void
    {
        Schema::table('unite_reservations', function (Blueprint $table) {
            $table->dropColumn(['guest_count', 'notes']);
        });
    }
};
