<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeSlide extends Model
{
    protected $fillable = ['title','subtitle','image','button_text','button_url','sort_order','is_active'];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];
}
