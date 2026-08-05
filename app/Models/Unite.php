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
}
