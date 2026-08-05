<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('Provider user ID');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unsignedBigInteger('transfer_policy_id')->nullable();
            $table->foreign('transfer_policy_id')->references('id')->on('transfer_policies')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('platform_fee', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)
                ->comment('amount - tax_amount - platform_fee');
            $table->enum('method', ['bank_transfer', 'cash', 'check', 'digital_wallet'])
                ->default('bank_transfer');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])
                ->default('pending');
            $table->string('reference')->nullable()->comment('Bank ref or transaction ID');
            $table->text('notes')->nullable();
            $table->date('scheduled_date')->nullable();
            $table->timestamp('transferred_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()
                ->comment('Admin who created this transfer');
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_transfers');
    }
};
