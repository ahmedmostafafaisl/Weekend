<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin-configurable boolean flags.  Starts empty — the seeder
     * populates the known keys with safe defaults (all disabled).
     *
     * This is intentionally narrow: only flags that gate real
     * application behaviour live here.  The two initial keys are:
     *
     *   allow_unites_without_subscription
     *       When true the property-subscription gate in
     *       UniteController::store() is bypassed.  Enable this to run
     *       a free-trial period for property listings.
     *
     *   allow_ads_without_subscription
     *       When true the ad-subscription gate in AdController::store()
     *       is bypassed.  Enable this to run a free-trial period for
     *       ad listings.
     */
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label_en');
            $table->string('label_ar');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
