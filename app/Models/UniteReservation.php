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
        'period_type',
        'from_time',
        'to_time',
        'price',
        'status',
        'guest_count',
        'notes',
    ];

    protected $casts = [
        'reservation_date' => 'date',
        'price' => 'float',
    ];

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
