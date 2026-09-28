<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MultiBookingGroup extends Model
{
    protected $fillable = [
        'user_id',
        'department_id',
        'payment_id',
        'reservation_date',
        'end_date',
        'period_type',
        'from_time',
        'to_time',
        'total_price',
        'total_amount',
        'status',
        'payment_url',
    ];

    protected $casts = [
        'reservation_date' => 'date',
        'end_date' => 'date',
        'total_price' => 'float',
        'total_amount' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function reservations()
    {
        return $this->hasMany(UniteReservation::class);
    }
}
