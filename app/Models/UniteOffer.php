<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UniteOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'unite_id',
        'name',
        'start',
        'end',
        'morning_price',
        'evening_price',
        'full_day_price',
        'day_hour_price',
        'night_hour_price',
        'status',
    ];

    public function unite()
    {
        return $this->belongsTo(Unite::class);
    }
}
