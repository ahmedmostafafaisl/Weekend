<?php

namespace Database\Seeders;

use App\Models\AdPackage;
use App\Models\PropertyPackage;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SubscriptionsTableSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::pluck('id')->values();
        $propertyPackages = PropertyPackage::pluck('id')->values();
        $adPackages = AdPackage::pluck('id')->values();

        if ($users->isEmpty()) {
            return;
        }

        $subscriptions = [];

        // 25 property subscriptions -- every PropertyPackage is now
        // type='count', so each subscription's count mirrors its actual
        // package's own count (not a random, unrelated number), with
        // percentage/start_date/end_date left null -- matching exactly
        // how GeideaPaymentService::activateSubscription() (and the
        // admin-dashboard equivalent, SubscriptionController::
        // prepareSubscriptionData()) actually populate a real count-type
        // subscription: only the field matching the package's own type
        // ever gets set, everything else stays null.
        if ($propertyPackages->isNotEmpty()) {
            $propertyPackageModels = PropertyPackage::whereIn('id', $propertyPackages)->get()->keyBy('id');

            for ($i = 1; $i <= 25; $i++) {
                $package = $propertyPackageModels[$propertyPackages[($i - 1) % $propertyPackages->count()]];

                $subscriptions[] = [
                    'user_id' => $users[($i - 1) % $users->count()],
                    'type' => 'property',
                    'package_id' => $package->id,
                    'amount' => (float) $package->price,
                    'start_date' => null,
                    'end_date' => null,
                    'percentage' => null,
                    'count' => $package->count,
                    'status' => $i % 6 === 0 ? 'inactive' : 'active',
                ];
            }
        }

        // 25 ad subscriptions
        if ($adPackages->isNotEmpty()) {
            for ($i = 1; $i <= 25; $i++) {
                $startDate = Carbon::today()->subDays(rand(0, 10));
                $endDate = (clone $startDate)->addDays(15);

                $subscriptions[] = [
                    'user_id' => $users[($i - 1) % $users->count()],
                    'type' => 'ad',
                    'package_id' => $adPackages[($i - 1) % $adPackages->count()],
                    'amount' => 99 + ($i * 3),
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'percentage' => rand(1, 10),
                    'count' => rand(1, 5),
                    'status' => $i % 5 === 0 ? 'inactive' : 'active',
                ];
            }
        }

        foreach ($subscriptions as $subscription) {
            // NOTE: for property subscriptions, start_date is always null
            // now (count-type packages have no date range), so this match
            // key's uniqueness for that half of $subscriptions currently
            // relies on user_id alone never repeating within the loop --
            // true today only because this loop runs exactly 25 times and
            // UsersTableSeeder creates exactly 25 users, so user_id % 25
            // happens to visit every user exactly once. If either count
            // ever changes independently, multiple iterations could
            // collapse into the same row here instead of creating
            // distinct subscriptions. Verified this seeder does produce
            // 25 genuinely distinct property subscriptions today; noting
            // the fragility rather than leaving it silently implicit.
            Subscription::updateOrCreate(
                [
                    'user_id' => $subscription['user_id'],
                    'type' => $subscription['type'],
                    'package_id' => $subscription['package_id'],
                    'start_date' => $subscription['start_date'],
                ],
                $subscription
            );
        }
    }
}
