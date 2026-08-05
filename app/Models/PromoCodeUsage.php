<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoCodeUsage extends Model
{
    protected $fillable = [
        'promo_code_id',
        'user_id',
        'payment_id',
        'discount_amount',
        'original_amount',
        'final_amount',
    ];

    protected $casts = [
        'discount_amount' => 'float',
        'original_amount' => 'float',
        'final_amount' => 'float',
    ];

    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
