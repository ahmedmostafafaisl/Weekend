<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UniteImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'unite_id',
        'image',
    ];

    public function unite()
    {
        return $this->belongsTo(Unite::class, 'unite_id');
    }
}
