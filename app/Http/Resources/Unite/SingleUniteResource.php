<?php

namespace App\Http\Resources\Unite;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SingleUniteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $detail = $this->getDetailModel();
        $vendor = $this->department?->user;

        $vendorRating = 0;
        $vendorReviewsCount = 0;

        if ($vendor) {
            $vendorReviewsCount = $vendor->receivedVendorRatings->count();
            $vendorRating = round((float) $vendor->receivedVendorRatings->avg('rating'), 1);
        }

        $isFavorite = false;
        if (auth('sanctum')->check()) {
            $isFavorite = $this->favorites->contains('user_id', auth('sanctum')->id());
        } elseif (auth()->check()) {
            $isFavorite = $this->favorites->contains('user_id', auth()->id());
        }

        return [
            'id' => $this->id,
            'department_id' => $this->department_id,
            'type' => $this->type,
            'name' => $this->name,
            'description' => $this->description,
            'city' => $this->city,
            'location_name' => $this->location_name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'reservation_deposit' => $this->reservation_deposit,
            'reservation_deposit_type' => $this->reservation_deposit_type,
            'reservation_deposit_amount' => $this->reservation_deposit_amount,
            'insurance' => $this->insurance,
            'insurance_amount' => $this->insurance_amount,
            'insurance_amount_type' => $this->insurance_amount_type,
            'add_to_story' => $this->add_to_story,
            'refund_policy' => $this->refund_policy,
            'additional_terms' => $this->additional_terms,
            'status' => $this->status,
            'insurance_policy_id' => $this->insurance_policy_id,
            'insurance_policy' => $this->insurancePolicy?->name,

            'images' => $this->images->map(fn ($img) => asset($img->image))->values(),
            'rating' => round((float) ($this->ratings_avg_rating ?? 0), 1),
            'total_rating_count' => $this->ratings_count ?? 0,
            'unit_area' => $this->getUnitArea($detail),
            'families_and_singles' => $this->families_and_singles,
            'insurance_fee' => $this->insurance_amount,
            'is_most_viewed' => ($this->views_count ?? 0) >= 100,
            'price_per_night' => $this->getPricePerNight(),

            'features' => $this->features->map(function ($feature) {
                return [
                    'id' => $feature->id,
                    'name' => $feature->name,
                    'image' => null,
                    'desc' => $feature->description,
                    'status' => $feature->status,
                ];
            })->values(),
            'new_features' => $this->newFeatures->map(function ($feature) {
                return [
                    'id' => $feature->id,
                    'title' => $feature->title,
                    'description' => $feature->description,
                ];
            })->values(),
            'offers' => $this->offers->map(function ($offer) {
                $base = [
                    'id' => $offer->id,
                    'name' => $offer->name,
                    'start' => $offer->start,
                    'end' => $offer->end,
                ];

                // Stadiums are hourly-only — morning_price/evening_price/
                // full_day_price are always null for this type, since
                // offers here use day_hour_price/night_hour_price instead,
                // matching the admin dashboard's offers form and the
                // standalone offers pages (fixed a few sessions ago).
                if ($this->type === 'stadium') {
                    $base['day_hour_price'] = $offer->day_hour_price;
                    $base['night_hour_price'] = $offer->night_hour_price;
                } else {
                    $base['morning_price'] = $offer->morning_price;
                    $base['evening_price'] = $offer->evening_price;
                    $base['full_day_price'] = $offer->full_day_price;
                }

                $base['status'] = $offer->status;

                return $base;
            })->values(),

            'slots' => $this->formatSlotsByType(),

            'prices' => $this->formatPricesByType(),

            'reservations' => $this->reservations->map(function ($reservation) {
                return [
                    'id' => $reservation->id,
                    'user_id' => $reservation->user_id,
                    'reservation_date' => optional($reservation->reservation_date)->format('Y-m-d'),
                    'period_type' => $reservation->period_type,
                    'from_time' => $reservation->from_time,
                    'to_time' => $reservation->to_time,
                    'price' => $reservation->price,
                    'status' => $reservation->status,
                ];
            })->values(),

            // Not applicable to stadiums — hourly-only booking has no
            // capacity-tier concept. Key stays present (empty array) rather
            // than vanishing, so the response shape is consistent across
            // every venue type.
            'packages' => $this->type === 'stadium' ? [] : $this->packages->map(function ($package) {
                return [
                    'id' => $package->id,
                    'name' => $package->name,
                    'men_capacity' => $package->men_capacity,
                    'women_capacity' => $package->women_capacity,
                    'price' => $package->price,
                ];
            })->values(),

            // Genuinely universal across all 4 venue types, unlike the
            // capacity-tier 'packages' above it — package booking is an
            // optional add-on available equally to stadium/hall/lounge/camp.
            // Which booking systems this specific venue actually supports —
            // reuses the same centralized matrix the reservation-creation
            // flow itself validates against (Unite::allowedPeriodTypes()),
            // so this can never drift out of sync with what a booking
            // attempt would actually be allowed to use.
            'available_booking_systems' => $this->allowedPeriodTypes(),

            'package_booking_enabled' => (bool) $this->package_booking_enabled,
            'booking_packages' => $this->bookingPackages->map(function ($pkg) {
                return [
                    'id' => $pkg->id,
                    'name' => $pkg->name,
                    'booking_type' => $pkg->booking_type,
                    // Only the fields relevant to this package's own mode
                    // are meaningful — the other mode's fields are null.
                    'day' => $pkg->day,
                    'start_time' => $pkg->start_time,
                    'end_time' => $pkg->end_time,
                    'day_from' => $pkg->day_from,
                    'day_to' => $pkg->day_to,
                    'duration_days' => $pkg->duration_days,
                    'price' => $pkg->price,
                    'status' => $pkg->status,
                    // Free text the provider typed in, not a relation.
                    'services' => $pkg->services ?? [],
                ];
            })->values(),

            // Viewing appointments — a customer schedules a visit to
            // inspect the venue before booking it, picking one of these
            // predefined weekly slots. Only active slots are exposed here
            // (an inactive one the provider disabled shouldn't appear as
            // bookable), matching how offers/packages/etc. already filter
            // by status before reaching this response.
            'viewing_deposit_enabled' => (bool) $this->viewing_deposit_enabled,
            'viewing_deposit_refundable' => $this->viewing_deposit_enabled ? (bool) $this->viewing_deposit_refundable : null,
            'viewing_deposit_amount' => $this->viewing_deposit_enabled && $this->viewing_deposit_amount
                ? (float) $this->viewing_deposit_amount
                : null,
            'viewing_times' => $this->viewingTimes
                ->where('status', 'active')
                ->map(function ($vt) {
                    return [
                        'id' => $vt->id,
                        'day_of_week' => $vt->day_of_week,
                        'start_time' => $vt->start_time,
                        'end_time' => $vt->end_time,
                    ];
                })->values(),

            'is_favorite' => $isFavorite,

            'location_data' => [
                'location_address' => $this->location_name,
                'lat' => $this->latitude,
                'lng' => $this->longitude,
            ],

            'vendor_data' => [
                'vendor_id' => $vendor?->id,
                'vendor_name' => $vendor?->commercial_name ?: $vendor?->name,
                'vendor_address' => $this->department?->location,
                'vendor_image' => $vendor?->photo_url,
                'vendor_rating' => $vendorRating,
                'vendor_reviews_count' => $vendorReviewsCount,
            ],
            'department' => $this->department ? [
                'id' => $this->department->id,
                'name' => $this->department->name,
                'phone' => (string) $this->department->phone,
                'facebook' => $this->department->facebook,
                'twitter' => $this->department->twitter,
                'instagram' => $this->department->instagram,
                'youtube' => $this->department->youtube,
                'website' => $this->department->website,
                'whatsapp' => $this->department->whatsapp,
                'snapchat' => $this->department->snapchat,
                'tiktok' => $this->department->tiktok,
            ] : null,
            'details' => $detail,
            'additional_unites' => $this->getAdditionalUnites(),
        ];
    }

    protected function getDetailModel()
    {
        // All 4 venue types now share one UniteDetail model/relation —
        // this method is kept for call-site stability, it just no longer
        // needs to branch on type.
        return $this->detail;
    }

    protected function getUnitArea($detail)
    {
        if (! $detail) {
            return null;
        }

        return match ($this->type) {
            'lounge' => $detail->area ?? null,
            'stadium' => isset($detail->width, $detail->length) ? $detail->width.' x '.$detail->length : null,
            'camp' => isset($detail->width, $detail->length) ? $detail->width.' x '.$detail->length : null,
            'hall' => $detail->max_capacity ?? null,
            default => null,
        };
    }

    protected function getPricePerNight()
    {
        $price = $this->prices->first();

        if (! $price) {
            return null;
        }

        if ($this->type === 'stadium') {
            return $price->price;
        }

        return $price->full_price ?? $price->evening_price ?? $price->morning_price;
    }

    protected function formatSlotsByType()
    {
        if (in_array($this->type, ['stadium', 'hall'])) {
            return $this->slots->map(function ($slot) {
                return [
                    'id' => $slot->id,
                    'day_of_week' => $slot->day_of_week,
                    'full_start' => $slot->full_start,
                    'full_end' => $slot->full_end,
                    'status' => $slot->status,
                ];
            })->values();
        }

        return $this->slots->map(function ($slot) {
            return [
                'id' => $slot->id,
                'day_of_week' => $slot->day_of_week,
                'morning_start' => $slot->morning_start,
                'morning_end' => $slot->morning_end,
                'evening_start' => $slot->evening_start,
                'evening_end' => $slot->evening_end,
                'full_start' => $slot->full_start,
                'full_end' => $slot->full_end,
                'status' => $slot->status,
            ];
        })->values();
    }

    protected function formatPricesByType()
    {
        if ($this->type === 'stadium') {
            return $this->prices->map(function ($price) {
                return [
                    'id' => $price->id,
                    'day' => $price->day,
                    'price' => $price->price,
                ] + $this->hourlyFields($price);
            })->values();
        }

        if ($this->type === 'hall') {
            return $this->prices->map(function ($price) {
                return [
                    'id' => $price->id,
                    'day' => $price->day,
                    'full_price' => $price->full_price,
                ] + $this->hourlyFields($price);
            })->values();
        }

        return $this->prices->map(function ($price) {
            return [
                'id' => $price->id,
                'day' => $price->day,
                'morning_price' => $price->morning_price,
                'evening_price' => $price->evening_price,
                'full_price' => $price->full_price,
            ] + $this->hourlyFields($price);
        })->values();
    }

    /**
     * Returns the hourly pricing block for a price row.
     * Always included so the app knows whether hourly is available.
     */
    private function hourlyFields($price): array
    {
        return [
            'hourly_enabled' => (bool) $price->hourly_enabled,
            'day_hour_price' => $price->hourly_enabled ? (float) $price->day_hour_price : null,
            'night_hour_price' => $price->hourly_enabled ? (float) ($price->night_hour_price ?? $price->day_hour_price) : null,
            'day_start' => $price->hourly_enabled ? ($price->day_start ?? '06:00') : null,
            'day_end' => $price->hourly_enabled ? ($price->day_end ?? '18:00') : null,
            'min_booking_minutes' => $price->hourly_enabled ? ($price->min_booking_minutes ?? 60) : null,
        ];
    }

    protected function getAdditionalUnites()
    {
        return $this->department?->unites
            ? $this->department->unites
                ->where('id', '!=', $this->id)
                ->take(5)
                ->map(function ($unite) {
                    $image = $unite->images->first();
                    $price = $unite->prices->first();

                    return [
                        'id' => $unite->id,
                        'image' => $image ? asset($image->image) : null,
                        'rating' => 0,
                        'name' => $unite->name,
                        'location_address' => $unite->location_name,
                        'price' => $unite->type === 'stadium'
                            ? ($price?->price)
                            : ($price?->full_price ?? $price?->evening_price ?? $price?->morning_price),
                        'is_favorite' => false,
                    ];
                })
                ->values()
            : [];
    }
}
