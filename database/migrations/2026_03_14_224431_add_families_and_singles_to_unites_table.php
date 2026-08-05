<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unites', function (Blueprint $table) {
            $table->boolean('families_and_singles')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('unites', function (Blueprint $table) {
            $table->dropColumn('families_and_singles');
        });
    }
};
