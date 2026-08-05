<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FavoriteUnite extends Model
{
    protected $fillable = [
        'user_id',
        'unite_id',
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
