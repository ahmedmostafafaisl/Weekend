<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PromoCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'discount_type',
        'discount_value',
        'min_amount',
        'max_discount',
        'max_uses',
        'max_uses_per_user',
        'starts_at',
        'expires_at',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'discount_value' => 'float',
        'min_amount' => 'float',
        'max_discount' => 'float',
        'is_active' => 'boolean',
        'starts_at' => 'date',
        'expires_at' => 'date',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function usages()
    {
        return $this->hasMany(PromoCodeUsage::class);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\Admin::class, 'created_by');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Calculate the discount amount for a given order total.
     * Respects min_amount and max_discount caps.
     */
    public function calculateDiscount(float $amount): float
    {
        if ($this->discount_type === 'percentage') {
            $discount = round($amount * $this->discount_value / 100, 2);
            if ($this->max_discount !== null) {
                $discount = min($discount, $this->max_discount);
            }
        } else {
            $discount = min($this->discount_value, $amount); // never discount more than the total
        }

        return max(0, $discount);
    }

    /**
     * Check if this code is valid for a given user and amount.
     * Returns null if valid, or an error message string.
     */
    public function validate(float $amount, ?int $userId = null): ?string
    {
        if (! $this->is_active) {
            return 'Promo code is not active.';
        }

        $today = Carbon::today();

        if ($this->starts_at && $today->lt($this->starts_at)) {
            return 'Promo code is not yet valid.';
        }

        if ($this->expires_at && $today->gt($this->expires_at)) {
            return 'Promo code has expired.';
        }

        if ($this->min_amount !== null && $amount < $this->min_amount) {
            return "Minimum order amount for this code is {$this->min_amount}.";
        }

        if ($this->max_uses !== null && $this->usages()->count() >= $this->max_uses) {
            return 'Promo code usage limit has been reached.';
        }

        if ($userId && $this->max_uses_per_user !== null) {
            $userUses = $this->usages()->where('user_id', $userId)->count();
            if ($userUses >= $this->max_uses_per_user) {
                return 'You have already used this promo code the maximum number of times.';
            }
        }

        return null; // valid
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhereDate('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhereDate('expires_at', '>=', now()));
    }
}
