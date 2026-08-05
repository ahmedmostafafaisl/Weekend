<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceFee extends Model
{
    protected $fillable = [
        'key',
        'label_en',
        'label_ar',
        'amount',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * The known category keys — tied to actual payment code paths (see the
     * three call sites in UniteReservationRepository and
     * SubscriptionController). Kept here as the single source of truth for
     * what the seeder creates and what the admin settings page can edit,
     * rather than letting an admin type in an arbitrary new key that has
     * no corresponding application logic to ever charge it.
     */
    public const KEYS = ['reservation', 'ad_package', 'property_package'];

    /**
     * The single source of truth every payment flow calls to find out how
     * much (if anything) to add on top of the base price for a given
     * category. Returns 0.0 if the category has no row yet, or if it's
     * been explicitly disabled — callers never need to null-check.
     */
    public static function feeFor(string $key): float
    {
        $fee = static::where('key', $key)->where('is_active', true)->first();

        return $fee ? (float) $fee->amount : 0.0;
    }
}
