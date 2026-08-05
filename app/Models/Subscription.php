<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'package_id',
        'amount',
        'start_date',
        'end_date',
        'percentage',
        'status',
        'count',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'amount' => 'decimal:2',
        'percentage' => 'decimal:2',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function adPackage()
    {
        return $this->belongsTo(AdPackage::class, 'package_id');
    }

    public function propertyPackage()
    {
        return $this->belongsTo(PropertyPackage::class, 'package_id');
    }

    /** The payment record that funded this subscription. */
    public function payment()
    {
        return $this->hasOne(Payment::class, 'subscription_id');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Returns the correct package regardless of type.
     * NOTE: not an Eloquent relation — cannot be eager-loaded by name.
     * Use adPackage / propertyPackage relations for eager loading.
     */
    public function resolvedPackage(): AdPackage|PropertyPackage|null
    {
        return $this->type === 'ad'
            ? $this->adPackage
            : $this->propertyPackage;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check whether this subscription should be considered expired
     * regardless of what the status column currently says.
     *
     * Expired when:
     *   - end_date is set AND end_date < today
     *   - OR count is set AND count <= 0
     */
    public function isExpiredByRules(): bool
    {
        if ($this->end_date && $this->end_date->lt(now()->startOfDay())) {
            return true;
        }

        if (! is_null($this->count) && $this->count <= 0) {
            return true;
        }

        return false;
    }

    /**
     * Mark this subscription as inactive if expiry rules are met.
     * Returns true if the status was changed.
     */
    public function expireIfDue(): bool
    {
        if ($this->status === 'active' && $this->isExpiredByRules()) {
            $this->update(['status' => 'inactive']);

            return true;
        }

        return false;
    }
}
