<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $guard_name = 'admin';

    protected $fillable = [
        'name',
        'email',
        'password',

    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public const ORDER = ['name', 'email'];

    public const UPLOADPATH = 'images/admins/';

    public const UPLOADFIELDS = [];

    // ✅ بدل type:super_admin استخدم role:super_admin
    public function getSuperAdminAttribute(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function nameOnHeader()
    {
        return strlen($this->name) > 10 ? substr($this->name, 0, 10).'..' : $this->name;
    }

    // ✅ بدل scopeAdmin اللي كان مبني على type

    public function reviewerScopes()
    {
        return $this->hasMany(AdminReviewerScope::class);
    }

    public function isUnrestrictedReviewer(): bool
    {
        return $this->hasRole('reviewer') && $this->reviewerScopes()->doesntExist();
    }

    public function reservationScopeQuery(): ?\Closure
    {
        if (! $this->hasRole('reviewer')) {
            return null;
        }

        $scopes = $this->reviewerScopes()->get();

        if ($scopes->isEmpty()) {
            return null; // reviewer with no scope rows = see all
        }

        $uniteIds = $scopes->whereNotNull('unite_id')->pluck('unite_id');
        $types = $scopes->whereNotNull('unite_type')->whereNull('unite_id')->pluck('unite_type');

        return function ($query) use ($uniteIds, $types) {
            $query->whereHas('unite', function ($q) use ($uniteIds, $types) {
                $q->where(function ($inner) use ($uniteIds, $types) {
                    if ($uniteIds->isNotEmpty()) {
                        $inner->orWhereIn('id', $uniteIds);
                    }
                    if ($types->isNotEmpty()) {
                        $inner->orWhereIn('type', $types);
                    }
                });
            });
        };
    }

    public function scopeAdmin($query)
    {
        return $query->role('admin'); // spatie scope
    }
}
