<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])
                ->default('pending')
                ->after('is_active');
            $table->foreignId('reviewed_by_admin_id')->nullable()
                ->after('approval_status')
                ->constrained('admins')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by_admin_id');
            $table->text('rejection_note')->nullable()->after('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by_admin_id']);
            $table->dropColumn(['approval_status', 'reviewed_by_admin_id', 'reviewed_at', 'rejection_note']);
        });
    }
};
