<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A customer's rating + review of a specific VENUE (Unite), tied to the
 * specific completed RESERVATION it came from — a customer who books the
 * same venue multiple times gets one rating opportunity per booking, not
 * one total (see unique('reservation_id') in the migration). This is what
 * powers Unite's `ratings`/`ratings_avg_rating`/`ratings_count` aggregates
 * used throughout the API (venue listings, search results, the show page)
 * and the post-booking "rate this venue" flow.
 *
 * reservation_id is nullable — historical ratings created before this
 * column existed (or via the legacy unites/{id}/rate endpoint) have no
 * reservation tie and still count toward the venue's aggregate rating.
 *
 * Distinction from VendorRating: this rates the PLACE itself. VendorRating
 * rates the PROVIDER (the person/business who owns the venue) — their
 * responsiveness, service quality, etc. A customer could rate a venue 5
 * stars while rating its provider 3 stars (or vice versa) — they are
 * deliberately independent signals, not duplicates of the same concept.
 *
 * @see VendorRating for ratings of the provider/vendor user, not the venue
 */
class UniteRating extends Model
{
    protected $fillable = [
        'unite_id',
        'reservation_id',
        'user_id',
        'rating',
        'review',
    ];

    public function unite()
    {
        return $this->belongsTo(Unite::class, 'unite_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reservation()
    {
        return $this->belongsTo(UniteReservation::class, 'reservation_id');
    }
}
