<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->foreignId('property_id')
                ->nullable()
                ->after('user_id')
                ->constrained('unites')
                ->nullOnDelete();

            $table->enum('type', ['property', 'ad'])->default('ad')->after('user_id');
            $table->timestamp('activated_at')->nullable()->after('media');
            $table->timestamp('expires_at')->nullable()->after('activated_at');
            $table->boolean('is_active')->default(false)->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('property_id');
            $table->dropColumn(['type', 'activated_at', 'expires_at', 'is_active']);
        });
    }
};
