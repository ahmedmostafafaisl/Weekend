<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->decimal('requested_amount', 12, 2);
            $table->enum('preferred_method', ['bank_transfer', 'cash', 'check', 'digital_wallet'])
                ->default('bank_transfer');
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_response')->nullable();
            $table->unsignedBigInteger('transfer_id')->nullable()
                ->comment('Linked to provider_transfers when approved');
            $table->foreign('transfer_id')->references('id')->on('provider_transfers')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_requests');
    }
};
