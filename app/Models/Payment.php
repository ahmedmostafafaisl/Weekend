<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'reservation_id',
        'subscription_id',
        'payment_type',
        'amount',
        'payment_id',
        'status',
        'phone',
        'promo_code_id',
        'discount_amount',
        'original_amount',
        'service_fee_amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'service_fee_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            do {
                $reference = 'PAY-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
            } while (self::where('reference_id', $reference)->exists());

            $payment->reference_id = $reference;
        });
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reservation()
    {
        return $this->belongsTo(UniteReservation::class, 'reservation_id');
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PaymentItem::class);
    }

    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function promoCodeUsage()
    {
        return $this->hasOne(PromoCodeUsage::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function isRefundFailed(): bool
    {
        return $this->status === 'refund_failed';
    }

    public function isForReservation(): bool
    {
        return $this->reservation_id !== null;
    }

    public function isForSubscription(): bool
    {
        return $this->subscription_id !== null;
    }

    public function hasDiscount(): bool
    {
        return $this->discount_amount !== null && $this->discount_amount > 0;
    }
}
