<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UniteViewingTime extends Model
{
    use SoftDeletes;

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
