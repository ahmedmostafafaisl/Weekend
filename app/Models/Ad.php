<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Ad extends Model
{
    protected $fillable = [
        'user_id',
        'property_id',
        'type',
        'title',
        'description',
        'thumbnail',
        'media',
        'is_active',
        'activated_at',
        'expires_at',
        'city',
        'target_audience',
        'target_user_type',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'target_audience' => 'string',
        'target_user_type' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function property()
    {
        return $this->belongsTo(Unite::class, 'property_id');
    }

    public function views()
    {
        return $this->hasMany(AdView::class);
    }

    public function comments()
    {
        return $this->hasMany(AdComment::class)->latest();
    }

    public function visibleComments()
    {
        return $this->hasMany(AdComment::class)->where('is_visible', true)->latest();
    }

    public function targetUsers()
    {
        return $this->belongsToMany(User::class, 'ad_target_users');
    }

    /**
     * Filter ads for a specific user — respect city and target_audience,
     * but a user's OWN ads always show regardless of any targeting
     * restriction (they should obviously be able to see their own ad in
     * the feed even if, say, its target city no longer matches where
     * they're currently browsing from).
     */
    public function scopeForUser(Builder $query, ?int $userId = null, ?string $city = null, ?string $audience = null, ?string $userType = null): Builder
    {
        return $query->where(function (Builder $outer) use ($userId, $city, $audience, $userType) {
            if ($userId) {
                $outer->orWhere('user_id', $userId);
            }

            $outer->orWhere(function (Builder $targeting) use ($userId, $city, $audience, $userType) {
                if ($userId) {
                    $targeting->where(function ($q) use ($userId) {
                        $q->whereDoesntHave('targetUsers')
                            ->orWhereHas('targetUsers', fn ($u) => $u->where('users.id', $userId));
                    });
                }
                if ($city) {
                    $targeting->where(fn ($q) => $q->whereNull('city')->orWhere('city', 'like', "%{$city}%"));
                }
                // BUG FIX: audience === 'both' used to be treated identically
                // to audience === null (skip filtering entirely) because of
                // the `$audience !== 'both'` guard below — meaning a viewer
                // explicitly restricted to 'both' (e.g. their gender is
                // unknown, so we deliberately don't want to show them
                // gender-specific ads) would still see 'men'-only and
                // 'women'-only ads regardless. 'both' now genuinely means
                // "only ads marked both," while null still means "no
                // audience restriction at all" (used when a caller
                // explicitly wants to see everything).
                if ($audience === 'both') {
                    $targeting->where('target_audience', 'both');
                } elseif ($audience) {
                    $targeting->where(fn ($q) => $q->where('target_audience', 'both')->orWhere('target_audience', $audience));
                }
                if ($userType && $userType !== 'all') {
                    $targeting->where(fn ($q) => $q->where('target_user_type', 'all')->orWhere('target_user_type', $userType));
                }
            });
        });
    }

    public function scopeActiveNow(Builder $query)
    {
        return $query->where('is_active', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now());
    }
}
