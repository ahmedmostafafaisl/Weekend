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

        // 25 property subscriptions
        if ($propertyPackages->isNotEmpty()) {
            for ($i = 1; $i <= 25; $i++) {
                $startDate = Carbon::today()->subDays(rand(0, 10));
                $endDate = (clone $startDate)->addDays(30);

                $subscriptions[] = [
                    'user_id' => $users[($i - 1) % $users->count()],
                    'type' => 'property',
                    'package_id' => $propertyPackages[($i - 1) % $propertyPackages->count()],
                    'amount' => 199 + ($i * 5),
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'percentage' => rand(5, 20),
                    'count' => rand(1, 10),
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
