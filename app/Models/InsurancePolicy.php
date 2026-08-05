<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsurancePolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',

    ];

    public function units()
    {
        return $this->hasMany(Unite::class);
    }
}
