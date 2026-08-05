<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyPackage extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'type',
        'duration',
        'percentage',
        'price',
        'image',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'package_id');
    }
}
