<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'count',
        'duration',
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
