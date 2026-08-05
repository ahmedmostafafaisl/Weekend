<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BUG FIX: families_and_singles was a plain boolean column, but every other
 * part of the app already treated it as a tri-state choice — the create/
 * edit dashboard forms present a 3-option dropdown (families / singles /
 * both), and the show page even does __('lang.'.$unite->families_and_singles)
 * to build a translation key from it, which only makes sense for a string
 * value, not a boolean. Because a boolean column collapses ANY non-empty
 * string ('families', 'singles', or 'both') to the same truthy value, all
 * 3 dropdown choices were silently indistinguishable from each other once
 * saved — which is almost certainly what "not stored or edit" was actually
 * describing, rather than a missing validation rule alone.
 *
 * Converts the column to a nullable string holding one of: families |
 * singles | both. Existing boolean data is preserved as a best-effort
 * migration (true → 'both', since that was likely intended to mean "no
 * restriction", false → null, meaning "not specified") — there's no way to
 * recover which of the 3 real states a boolean row was originally meant to
 * represent, so this is a reasonable, non-destructive default rather than
 * a guess presented as fact.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unites', function (Blueprint $table) {
            $table->renameColumn('families_and_singles', 'families_and_singles_old_bool');
        });

        Schema::table('unites', function (Blueprint $table) {
            $table->string('families_and_singles')->nullable()->after('status');
        });

        DB::table('unites')->where('families_and_singles_old_bool', true)
            ->update(['families_and_singles' => 'both']);

        Schema::table('unites', function (Blueprint $table) {
            $table->dropColumn('families_and_singles_old_bool');
        });
    }

    public function down(): void
    {
        Schema::table('unites', function (Blueprint $table) {
            $table->renameColumn('families_and_singles', 'families_and_singles_str');
        });

        Schema::table('unites', function (Blueprint $table) {
            $table->boolean('families_and_singles')->default(false)->after('status');
        });

        DB::table('unites')->whereNotNull('families_and_singles_str')
            ->update(['families_and_singles' => true]);

        Schema::table('unites', function (Blueprint $table) {
            $table->dropColumn('families_and_singles_str');
        });
    }
};
