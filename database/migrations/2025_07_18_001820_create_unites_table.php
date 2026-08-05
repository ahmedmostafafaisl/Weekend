<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('unites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id');
            $table->enum('type', ['stadium', 'hall', 'lounge', 'camp', 'other'])->comment('نوع الوحدة');

            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('location_name')->nullable();
            $table->string('city')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->boolean('reservation_deposit')->default(false);
            $table->enum('reservation_deposit_type', ['amount', 'percentage'])->default('amount')->nullable();
            $table->decimal('reservation_deposit_amount', 15, 2)->nullable();

            $table->boolean('insurance')->default(false);
            $table->decimal('insurance_amount', 15, 2)->nullable();

            $table->enum('refund_policy', ['free', 'flexible', 'moderate', 'strict'])->default('free');
            $table->text('additional_terms')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unites');
    }
};
