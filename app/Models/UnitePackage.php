<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitePackage extends Model
{
    use HasFactory;
    protected $fillable = [
        'unite_id',
        'name',
        'men_capacity',
        'women_capacity',
        'price',
    ];

    public function unite()
    {
        return $this->belongsTo(Unite::class, 'unite_id');
    }
}
