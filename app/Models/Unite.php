<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unite extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'type',
        'name',
        'description',
        'location_name',
        'city',
        'latitude',
        'longitude',
        'reservation_deposit',
        'reservation_deposit_type',
        'reservation_deposit_amount',
        'insurance',
        'insurance_amount',
        'refund_policy',
        'additional_terms',
        'status',
        'families_and_singles',
        'package_booking_enabled',
        'viewing_deposit_enabled',
        'viewing_deposit_refundable',
        'viewing_deposit_amount',
        'insurance_policy_id',
        'requires_approval',

    ];

    // Relationships
    public function detail()
    {
        return $this->hasOne(UniteDetail::class, 'unite_id');
    }

    /**
     * @deprecated Use detail() — kept temporarily so any code still calling
     * stadiumDetail()/hallDetail()/loungeDetail()/campDetail() (e.g. cached
     * views, third-party integrations) keeps working during the transition.
     * All four now point at the same single unite_details row.
     */
    public function stadiumDetail()
    {
        return $this->detail();
    }

    public function hallDetail()
    {
        return $this->detail();
    }

    public function loungeDetail()
    {
        return $this->detail();
    }

    public function campDetail()
    {
        return $this->detail();
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function images()
    {
        return $this->hasMany(UniteImage::class);
    }

    /**
     * The older, structured, filterable amenities list (has status,
     * queried in search). @see \App\Models\UniteFeature
     */
    public function features()
    {
        return $this->hasMany(UniteFeature::class);
    }

    public function offers()
    {
        return $this->hasMany(UniteOffer::class);
    }

    /**
     * Individual council (sitting area) entries — a lounge can have
     * several, each with its own optional type. See UniteCouncil for
     * the full explanation of why this replaced the old flat
     * council_type string.
     */
    public function councils()
    {
        return $this->hasMany(UniteCouncil::class);
    }

    public function reservations()
    {
        return $this->hasMany(UniteReservation::class);
    }

    public function slots()
    {
        return $this->hasMany(UniteSlot::class, 'unite_id');
    }

    public function prices()
    {
        return $this->hasMany(UnitePrice::class, 'unite_id');
    }

    // packages
    public function packages()
    {
        return $this->hasMany(UnitePackage::class, 'unite_id');
    }

    // booking packages — a genuinely different concept from packages()
    // above: day/time-window/price bundles a customer reserves directly,
    // not a men/women capacity tier. Available to every venue type as an
    // optional add-on (see package_booking_enabled).
    public function bookingPackages()
    {
        return $this->hasMany(UniteBookingPackage::class, 'unite_id');
    }

    // viewing appointments — a genuinely separate booking mechanism from
    // reservations/packages: a customer visits the physical venue to
    // inspect it before committing to an actual booking, not to use it
    // for an event.
    public function viewingTimes()
    {
        return $this->hasMany(UniteViewingTime::class, 'unite_id');
    }

    public function viewings()
    {
        return $this->hasMany(UniteViewing::class, 'unite_id');
    }

    // new features
    /**
     * The newer, simpler highlights list (JSON description, no status,
     * not used in search filtering). @see \App\Models\UniteNewFeature
     */
    public function newFeatures()
    {
        return $this->hasMany(UniteNewFeature::class, 'unite_id');
    }

    // new relationships
    /**
     * Ratings of THIS VENUE specifically — not the provider who owns it.
     *
     * @see \App\Models\UniteRating
     * @see User::receivedVendorRatings() for ratings of the provider instead
     */
    public function ratings()
    {
        return $this->hasMany(UniteRating::class, 'unite_id');
    }

    public function favorites()
    {
        return $this->hasMany(FavoriteUnite::class, 'unite_id');
    }

    public function views()
    {
        return $this->hasMany(UniteView::class, 'unite_id');
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'service_unite', 'unite_id', 'service_id');
    }

    public function insurancePolicy()
    {
        return $this->belongsTo(InsurancePolicy::class);
    }

    /**
     * The single source of truth for which period_types this venue can
     * accept at all, matching the reservation-level enforcement matrix:
     *   stadium -> hourly + package (never morning/evening/full_day)
     *   hall    -> full_day + package (never hourly/morning/evening)
     *   lounge  -> morning/evening/full_day + package, + hourly if enabled
     *   camp    -> same as lounge
     *
     * 'hourly' being present here for lounge/camp is necessary but not
     * sufficient — it only means the TYPE can support hourly at all; the
     * specific day's price row still needs hourly_enabled=true, which is
     * checked separately (and already correctly enforced) in
     * UniteReservationRepository::resolveHourlyPrice().
     * 'package' is only ever included when package_booking_enabled is on.
     */
    public function allowedPeriodTypes(): array
    {
        $types = match ($this->type) {
            'stadium' => ['hourly'],
            'hall' => ['full_day'],
            default => ['morning', 'evening', 'full_day', 'hourly'], // lounge, camp
        };

        if ($this->package_booking_enabled) {
            $types[] = 'package';
        }

        return $types;
    }
}
