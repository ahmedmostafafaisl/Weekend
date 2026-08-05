<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_reviewer_scopes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id');
            $table->foreign('admin_id')->references('id')->on('admins')->cascadeOnDelete();

            // Scope: null = all, set = restricted to this type
            $table->enum('unite_type', ['stadium', 'hall', 'lounge', 'camp', 'other'])->nullable();

            // Specific unite (overrides type if both set)
            $table->unsignedBigInteger('unite_id')->nullable();
            $table->foreign('unite_id')->references('id')->on('unites')->nullOnDelete();

            $table->timestamps();

            // Prevent duplicates
            $table->unique(['admin_id', 'unite_type', 'unite_id'], 'reviewer_scope_unique');
            $table->index('admin_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_reviewer_scopes');
    }
};
