<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UniteReservation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'unite_id',
        'user_id',
        'reservation_date',
        'end_date',
        'period_type',
        'from_time',
        'to_time',
        'price',
        'status',
        'guest_count',
        'notes',
        'unite_booking_package_id',
    ];

    protected $casts = [
        'reservation_date' => 'date',
        'end_date' => 'date',
        'price' => 'float',
    ];

    public function bookingPackage()
    {
        return $this->belongsTo(UniteBookingPackage::class);
    }

    public function unite()
    {
        return $this->belongsTo(Unite::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'reservation_id');
    }

    public function rating()
    {
        return $this->hasOne(UniteRating::class, 'reservation_id');
    }

    /**
     * A reservation is eligible to be rated once it's confirmed and its
     * date is not in the past — today or any future date is fine. A
     * reservation whose date has already passed is no longer ratable
     * under this rule.
     */
    public function isRatable(): bool
    {
        return $this->status === 'confirmed'
            && $this->reservation_date
            && ! $this->reservation_date->lt(now()->startOfDay());
    }

    /**
     * Shared date-range conflict query — the exact same overlap logic
     * UniteReservationRepository::ensureNoConflict() uses when actually
     * creating/rescheduling a reservation, extracted here so
     * AvailabilityService can ask "would this be available?" using the
     * identical formula, rather than risking a second implementation
     * drifting out of sync with the one that actually enforces it.
     *
     * $startDate/$endDate: the range being checked — pass the same date
     * for both for a normal single-day check. Time-overlap only applies
     * when BOTH the range being checked and the existing reservation are
     * genuinely single-day on the exact same date; a multi-day range on
     * either side occupies whole days regardless of specific hours.
     *
     * $bufferMinutes: handover/buffer time (requirement 4) — expands
     * each EXISTING reservation's occupied window symmetrically by this
     * many minutes on both sides before checking overlap against the new
     * request's own unmodified [fromTime, toTime]. Symmetric because the
     * venue genuinely needs the same handover time whether the new
     * request lands right before or right after an existing booking —
     * not just the "starts right after" case the requirement's own
     * example happens to illustrate. Defaults to 0 (no-op: ADDTIME/
     * SUBTIME by zero minutes changes nothing), so any existing caller
     * that doesn't pass it behaves exactly as before.
     */
    public function scopeConflicting($query, int $uniteId, string $startDate, ?string $endDate = null, ?string $fromTime = null, ?string $toTime = null, ?int $ignoreId = null, int $bufferMinutes = 0)
    {
        $endDate = $endDate ?? $startDate;
        $isMultiDayRequest = $endDate !== $startDate;

        $query->where('unite_id', $uniteId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where('reservation_date', '<=', $endDate)
                    ->where(function ($q2) use ($startDate) {
                        $q2->whereRaw('COALESCE(end_date, reservation_date) >= ?', [$startDate]);
                    });
            });

        if (! $isMultiDayRequest && $fromTime && $toTime) {
            $query->where(function ($q) use ($fromTime, $toTime, $bufferMinutes) {
                $q->whereNotNull('end_date')
                    ->orWhere(function ($q2) use ($fromTime, $toTime, $bufferMinutes) {
                        $q2->whereNull('end_date')
                            ->whereRaw('SUBTIME(from_time, SEC_TO_TIME(? * 60)) < ?', [$bufferMinutes, $toTime])
                            ->whereRaw('ADDTIME(to_time, SEC_TO_TIME(? * 60)) > ?', [$bufferMinutes, $fromTime]);
                    });
            });
        }

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query;
    }

    public function isPaid(): bool
    {
        return $this->payment?->isPaid() ?? false;
    }

    public function getStartsAtAttribute()
    {
        if (! $this->reservation_date || ! $this->from_time) {
            return null;
        }

        return Carbon::parse($this->reservation_date->format('Y-m-d').' '.$this->from_time);
    }
}
