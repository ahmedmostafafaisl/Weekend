<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreference extends Model
{
    protected $fillable = ['user_id', 'type', 'push_enabled', 'email_enabled'];

    protected $casts = [
        'push_enabled' => 'boolean',
        'email_enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── All supported notification types ──────────────────────────────────────
    public const TYPES = [
        'new_reservation_received' => 'New Booking Received',
        'reservation_confirmed' => 'Booking Confirmed',
        'reservation_cancelled' => 'Booking Cancelled',
        'reservation_cancelled_provider' => 'Booking Cancelled (Provider)',
        'reservation_pending_approval' => 'Booking Awaiting Approval',
        'payment_failed' => 'Payment Failed',
        'subscription_activated' => 'Subscription Activated',
        'promotion' => 'Promotions & Offers',
        'leave_review' => 'Leave a Review',
    ];

    /**
     * Check whether push is enabled for a given user + type.
     * Returns true (default) if no preference row exists yet.
     */
    public static function pushEnabled(int $userId, string $type): bool
    {
        $pref = static::where('user_id', $userId)->where('type', $type)->first();

        return $pref ? $pref->push_enabled : true;
    }

    /**
     * Check whether email is enabled for a given user + type.
     * Returns true (default) if no preference row exists yet.
     */
    public static function emailEnabled(int $userId, string $type): bool
    {
        $pref = static::where('user_id', $userId)->where('type', $type)->first();

        return $pref ? $pref->email_enabled : true;
    }
}
