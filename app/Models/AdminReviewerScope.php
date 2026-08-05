<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminReviewerScope extends Model
{
    protected $fillable = ['admin_id', 'unite_type', 'unite_id'];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function unite()
    {
        return $this->belongsTo(Unite::class);
    }
}
