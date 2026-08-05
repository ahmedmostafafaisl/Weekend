<?php

namespace Database\Seeders;

use App\Models\Unite;
use App\Models\UniteView;
use App\Models\User;
use Illuminate\Database\Seeder;

class UniteViewsTableSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::where('type', 'customer')->get();
        $unites = Unite::all();

        if ($unites->isEmpty()) {
            return;
        }

        foreach ($unites as $uIndex => $unite) {
            foreach ($customers as $cIndex => $customer) {
                if ((($uIndex + $cIndex) % 2) === 0) {
                    UniteView::updateOrCreate(
                        [
                            'unite_id' => $unite->id,
                            'user_id' => $customer->id,
                        ],
                        [
                            'ip_address' => '192.168.1.'.(($cIndex % 200) + 10),
                        ]
                    );
                }
            }

            for ($i = 1; $i <= 3; $i++) {
                UniteView::updateOrCreate(
                    [
                        'unite_id' => $unite->id,
                        'ip_address' => '10.0.'.$uIndex.'.'.$i,
                    ],
                    [
                        'user_id' => null,
                    ]
                );
            }
        }
    }
}
