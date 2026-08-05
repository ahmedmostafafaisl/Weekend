<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdComment extends Model
{
    protected $fillable = ['ad_id', 'user_id', 'body', 'is_visible'];

    protected $casts = ['is_visible' => 'boolean'];

    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scope: only visible comments (used for public-facing queries)
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }
}
