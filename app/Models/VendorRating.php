<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A customer's rating + review of a specific PROVIDER/VENDOR (a User with
 * type='provider') — their responsiveness, professionalism, communication,
 * etc., independent of any single venue they manage. Surfaced on User via
 * receivedVendorRatings() (ratings this provider has received) and
 * givenVendorRatings() (ratings this user has left for providers), and
 * aggregated into vendor_rating/vendor_reviews_count in SingleUniteResource
 * so a venue's page can show "this provider is rated 4.8 across all their
 * venues" alongside the venue's own UniteRating score.
 *
 * Distinction from UniteRating: this rates the PERSON/BUSINESS, not the
 * place. A provider managing 5 venues has one VendorRating reputation
 * shared across all of them, but each venue has its own independent
 * UniteRating score. These are deliberately separate signals.
 *
 * @see UniteRating for ratings of a specific venue, not its provider
 */
class VendorRating extends Model
{
    protected $fillable = [
        'vendor_user_id',
        'user_id',
        'rating',
        'review',
    ];

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_user_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
