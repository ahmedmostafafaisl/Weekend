<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles,Notifiable;

    protected $fillable = [
        'name',
        'commercial_name',
        'email',
        'phone',
        'password',
        'status',
        'type',
        'fcm_token',
        'provider_type',
        'nation',
        'gender',
        'id_number',
        'birth_date',
        'photo',
        'front_identity',
        'back_identity',
        'sak_image',
        'commercial_register_number',
        'organization_name',
        'commercial_register_image',
        'ownership',
        'delegation',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'email_verified_at' => 'datetime',
    ];

    /**
     * fields ordering in filteration
     */
    public const STATUSES = ['active', 'inactive'];

    public const TYPES = ['customer', 'provider'];

    public const PROVIDER_TYPES = ['individual', 'organization'];

    public const NATIONS = ['saudi', 'resident'];

    public const GENDERS = ['male', 'female'];

    const ORDER = ['name', 'email'];

    // #--------------------------------- RELATIONSHIPS

    // #--------------------------------- ATTRIBUTES

    // #--------------------------------- CUSTOM FUNCTIONS
    public function nameOnHeader()
    {
        if (strlen($this->name) > 10) {
            return \substr($this->name, 0, 10).'..';
        }

        return $this->name;
    }

    // #--------------------------------- SCOPES

    // #--------------------------------- ACCESSORS & MUTATORS

    // protected function password(): Attribute
    // {
    //     return Attribute::make(
    //         set: function ($value) {
    //             if ($value != null) {
    //                 return bcrypt($value);
    //             }
    //         },
    //     );
    // }

    // ✅ Accessors for image URLs
    public function getPhotoUrlAttribute()
    {
        return $this->photo ? asset('storage/'.$this->photo) : null;
    }

    public function getFrontIdentityUrlAttribute()
    {
        return $this->front_identity ? asset('storage/'.$this->front_identity) : null;
    }

    public function getBackIdentityUrlAttribute()
    {
        return $this->back_identity ? asset('storage/'.$this->back_identity) : null;
    }

    public function getSakImageUrlAttribute()
    {
        return $this->sak_image ? asset('storage/'.$this->sak_image) : null;
    }

    public function getCommercialRegisterImageUrlAttribute()
    {
        return $this->commercial_register_image ? asset('storage/'.$this->commercial_register_image) : null;
    }

    public function routeNotificationForFcm(): ?string
    {
        return $this->fcm_token ?: null;
    }

    public function reservations()
    {
        return $this->hasMany(UniteReservation::class);
    }

    public function ads()
    {
        return $this->hasMany(Ad::class);
    }

    // new relationships
    public function uniteRatings()
    {
        return $this->hasMany(UniteRating::class, 'user_id');
    }

    public function favoriteUnites()
    {
        return $this->belongsToMany(Unite::class, 'favorite_unites', 'user_id', 'unite_id');
    }

    public function uniteViews()
    {
        return $this->hasMany(UniteView::class, 'user_id');
    }

    /**
     * Ratings this user has RECEIVED as a provider/vendor — not to be
     * confused with ratings of their venues (see UniteRating on Unite).
     *
     * @see \App\Models\VendorRating
     */
    public function receivedVendorRatings()
    {
        return $this->hasMany(VendorRating::class, 'vendor_user_id');
    }

    /**
     * Ratings this user has GIVEN to providers/vendors (as a customer).
     *
     * @see \App\Models\VendorRating
     */
    public function givenVendorRatings()
    {
        return $this->hasMany(VendorRating::class, 'user_id');
    }

    public function suggestions()
    {
        return $this->hasMany(Suggestion::class);
    }
}
