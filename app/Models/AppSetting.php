<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = [
        'key',
        'label_en',
        'label_ar',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * The complete set of admin-togglable boolean flags.
     *
     * These keys are the single source of truth for what the seeder
     * creates and what the admin settings page can edit.  Each key
     * must correspond to a real application gate — an admin enabling
     * an unknown key would have no effect, but the fixed list prevents
     * confusion and keeps the admin UI predictable.
     */
    public const KEYS = [
        'allow_unites_without_subscription',
        'allow_ads_without_subscription',
    ];

    /**
     * Whether a setting flag is currently enabled.
     *
     * Safe to call even before the seeder runs or while the table is
     * being migrated — returns false for any unrecognised or missing
     * key rather than throwing.
     *
     * @param  string  $key  One of self::KEYS
     */
    public static function get(string $key): bool
    {
        $row = static::where('key', $key)->first();

        return $row ? (bool) $row->is_active : false;
    }
}
