<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unite_slots', function (Blueprint $table) {
            $table->enum('day_of_week', [
                'sunday',
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
                'saturday',
            ])->nullable()->after('unite_id');

            $table->dropColumn('slot_date');
        });
    }

    public function down(): void
    {
        Schema::table('unite_slots', function (Blueprint $table) {
            $table->date('slot_date')->nullable()->after('unite_id');
            $table->dropColumn('day_of_week');
        });
    }
};
