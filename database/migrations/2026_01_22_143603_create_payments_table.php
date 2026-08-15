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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // FK -> users.id
            $table->unsignedBigInteger('subscription_id')->nullable(); // FK -> subscriptions.id
            // Link payment to a booking reservation (nullable for subscription payments)
            $table->unsignedBigInteger('reservation_id')->nullable();
            // Proper FK constraints that were missing from the original migration
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->nullOnDelete();
            $table->foreign('reservation_id')->references('id')->on('unite_reservations')->nullOnDelete();

            $table->string('payment_type'); // geidea / tamara / tabby / cash ...
            $table->decimal('amount', 10, 2);

            $table->string('reference_id')->unique(); // auto-generated unique
            $table->string('payment_id')->nullable(); // gateway paymentIntentId / transaction id

            $table->enum('status', ['pending', 'paid', 'failed', 'refunded', 'refund_failed'])->default('pending');

            $table->string('phone')->nullable();

            $table->timestamps();

            $table->index(['status', 'payment_type']);
            $table->index('phone');
            $table->index(['user_id', 'created_at'], 'payments_user_id_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
