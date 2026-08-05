<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UniteView extends Model
{
    protected $fillable = [
        'unite_id',
        'user_id',
        'ip_address',
    ];

    public function unite()
    {
        return $this->belongsTo(Unite::class, 'unite_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
