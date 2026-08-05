<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'type',
        'location',
        'latitude',
        'longitude',
        'status',
        'facebook',
        'twitter',
        'instagram',
        'youtube',
        'website',
        'whatsapp',
        'snapchat',
        'tiktok',
        'phone',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(DepartmentImage::class);
    }

    public function unites(): HasMany
    {
        return $this->hasMany(Unite::class, 'department_id');
    }
}
