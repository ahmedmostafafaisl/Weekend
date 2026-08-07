<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UniteBookingPackage extends Model
{
    protected $fillable = [
        'unite_id',
        'name',
        'day',
        'start_time',
        'end_time',
        'price',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function unite()
    {
        return $this->belongsTo(Unite::class);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'unite_booking_package_service');
    }

    /**
     * Whether this package can be booked on the given specific day-of-week
     * name (lowercase, e.g. 'friday'). Maps it to its day-type category
     * (week_day/thursday/friday/saturday) using the exact same mapping
     * already used for price resolution elsewhere (see
     * UniteReservationRepository::resolvePrice() / the reservations
     * seeder), so 'day' here means the same thing it means everywhere
     * else in this project — not a specific day-of-week itself.
     */
    public function appliesToDay(string $dayOfWeek): bool
    {
        $category = match ($dayOfWeek) {
            'thursday' => 'thursday',
            'friday' => 'friday',
            'saturday' => 'saturday',
            default => 'week_day',
        };

        return $this->day === $category;
    }
}
