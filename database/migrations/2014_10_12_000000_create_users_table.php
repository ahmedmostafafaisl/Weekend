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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->bigInteger('phone')->unique()->nullable();
            $table->string('password');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->enum('type', ['customer', 'provider'])->default('customer');
            $table->text('fcm_token')->nullable();
            $table->enum('provider_type', ['individual', 'organization'])->default('individual')->nullable();
            $table->enum('nation', ['saudi', 'resident'])->default('saudi');
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('id_number')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('photo')->nullable();
            $table->string('front_identity')->nullable()->comment(' الصورة الهوية  الامامية ');
            $table->string('back_identity')->nullable()->comment(' الصورة الهوية   الخلفية ');
            $table->string('sak_image')->nullable()->comment(' صورة صك الملكية ');
            $table->string('commercial_register_number')->nullable();
            $table->string('organization_name')->nullable()->comment('  اسم المنظمة ');
            $table->string('commercial_register_image')->nullable()->comment(' صورة السجل التجاري ');
            $table->string('commercial_name')->nullable()->comment(' الاسم التجاري ');
            $table->boolean('ownership')->default(0)->comment('1=> مالك ', '2=> موكل');
            $table->string('delegation')->nullable()->comment(' التفويض ');

            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
