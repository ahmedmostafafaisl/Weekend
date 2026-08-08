<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links multiple registered users to a single viewing appointment — a
 * consultation for 3 people is one unite_viewings row with 3 rows here,
 * not 3 separate appointments. The primary booker
 * (unite_viewings.user_id, who pays the deposit if one applies) is
 * included in this pivot too, confirmed explicitly: "Number of People: 3"
 * counts the booker as one of the three, not as a 4th person on top of
 * three others.
 *
 * A unique constraint on (unite_viewing_id, user_id) prevents the same
 * person being attached to the same appointment twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unite_viewing_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unite_viewing_id')->constrained('unite_viewings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['unite_viewing_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unite_viewing_user');
    }
};
