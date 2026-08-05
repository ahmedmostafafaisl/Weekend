<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_group_id',
        'name',
        'description',
        'status',
        'sort_order',
    ];

    public function group()
    {
        return $this->belongsTo(ServiceGroup::class, 'service_group_id');
    }

    public function unites()
    {
        return $this->belongsToMany(Unite::class, 'service_unite', 'service_id', 'unite_id');
    }
}
