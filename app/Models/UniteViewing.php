<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UniteViewing extends Model
{
    protected $fillable = [
        'unite_id',
        'user_id',
        'unite_viewing_time_id',
        'viewing_date',
        'status',
        'deposit_required',
        'deposit_amount',
        'deposit_refundable',
    ];

    protected $casts = [
        'viewing_date' => 'date',
        'deposit_required' => 'boolean',
        'deposit_amount' => 'decimal:2',
        'deposit_refundable' => 'boolean',
    ];

    public function unite()
    {
        return $this->belongsTo(Unite::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function viewingTime()
    {
        return $this->belongsTo(UniteViewingTime::class, 'unite_viewing_time_id');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'unite_viewing_id');
    }
}
