<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unites', function (Blueprint $table) {

            $table->foreignId('insurance_policy_id')
                ->nullable()
                ->after('id')
                ->constrained('insurance_policies')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('unites', function (Blueprint $table) {
            $table->dropForeign(['insurance_policy_id']);
            $table->dropColumn('insurance_policy_id');
        });
    }
};
