<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use App\Models\VendorRating;
use Illuminate\Database\Seeder;

class VendorRatingsTableSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::where('type', 'customer')->get();
        $vendorUserIds = Department::whereNotNull('user_id')
            ->pluck('user_id')
            ->unique()
            ->values();

        if ($customers->isEmpty() || $vendorUserIds->isEmpty()) {
            return;
        }

        $reviews = [
            'مزود متعاون جدًا.',
            'محترف وسريع الاستجابة.',
            'كانت الخدمة رائعة.',
            'متعاون وسهل التعامل معه.',
            'أنصح بهذا المزود.',
        ];

        foreach ($vendorUserIds as $vIndex => $vendorUserId) {
            foreach ($customers as $cIndex => $customer) {
                if ($customer->id == $vendorUserId) {
                    continue;
                }

                // Same fix as UniteRatingsTableSeeder — see that file for
                // the full explanation. The old ((vIndex + cIndex) % 5) + 1
                // formula guaranteed every vendor would average to exactly
                // 3.0 with a customer count divisible by 5, making rating
                // filters/sorting non-differentiating across providers too.
                $tierHash = crc32('vendor-rating-tier-'.$vendorUserId) % 100;
                $baseRating = 2.0 + ($tierHash / 100) * 3.0;

                $noiseHash = crc32($customer->id.'-'.$vendorUserId) % 3;
                $variation = $noiseHash - 1;

                $ratingValue = max(1, min(5, (int) round($baseRating + $variation)));

                VendorRating::updateOrCreate(
                    [
                        'vendor_user_id' => $vendorUserId,
                        'user_id' => $customer->id,
                    ],
                    [
                        'rating' => $ratingValue,
                        'review' => $reviews[($vIndex + $cIndex) % count($reviews)],
                    ]
                );
            }
        }
    }
}
