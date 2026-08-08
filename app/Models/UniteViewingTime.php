<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UniteViewingTime extends Model
{
    protected $fillable = [
        'unite_id',
        'day_of_week',
        'start_time',
        'end_time',
        'status',
    ];

    public function unite()
    {
        return $this->belongsTo(Unite::class);
    }

    public function viewings()
    {
        return $this->hasMany(UniteViewing::class);
    }
}
