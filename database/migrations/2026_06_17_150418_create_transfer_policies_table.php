<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_policies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('transfer_days')->default(7)
                ->comment('Days after booking confirmation to transfer funds');
            $table->json('transfer_methods')
                ->comment('["bank_transfer","cash","check","digital_wallet"]');
            $table->decimal('tax_rate', 5, 2)->default(0)
                ->comment('Percentage e.g. 15.00 for 15% VAT');
            $table->decimal('platform_fee_rate', 5, 2)->default(0)
                ->comment('Platform commission percentage');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_policies');
    }
};
