<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unites', function (Blueprint $table) {
            $table->boolean('requires_approval')->default(false)->after('status')
                ->comment('When true, reservations need provider approval before Geidea payment is taken.');
        });
    }

    public function down(): void
    {
        Schema::table('unites', function (Blueprint $table) {
            $table->dropColumn('requires_approval');
        });
    }
};
