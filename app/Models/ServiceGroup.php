<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'label',
        'status',
        'sort_order',
    ];

    public function services()
    {
        return $this->hasMany(Service::class)->orderBy('sort_order')->orderBy('id');
    }
}
