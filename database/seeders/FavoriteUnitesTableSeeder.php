<?php

namespace Database\Seeders;

use App\Models\FavoriteUnite;
use App\Models\Unite;
use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteUnitesTableSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::where('type', 'customer')->get();
        $unites = Unite::all();

        if ($customers->isEmpty() || $unites->isEmpty()) {
            return;
        }

        foreach ($customers as $cIndex => $customer) {
            foreach ($unites as $uIndex => $unite) {
                if ((($cIndex + $uIndex) % 2) === 0) {
                    FavoriteUnite::updateOrCreate(
                        [
                            'user_id' => $customer->id,
                            'unite_id' => $unite->id,
                        ],
                        []
                    );
                }
            }
        }
    }
}
