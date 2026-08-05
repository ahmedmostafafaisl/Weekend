<?php

// database/seeders/StadiumTypeSeeder.php

namespace Database\Seeders;

use App\Models\StadiumType;
use Illuminate\Database\Seeder;

class StadiumTypeSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'name' => 'ملعب كرة قدم',
                'description' => 'ملعب لمباريات كرة القدم',
                'image' => null,
            ],
            [
                'name' => 'صالة كرة سلة',
                'description' => 'صالة داخلية لكرة السلة',
                'image' => null,
            ],
            [
                'name' => 'ملعب تنس',
                'description' => 'ملعب تنس بمعايير احترافية',
                'image' => null,
            ],
            [
                'name' => 'ملعب متعدد الاستخدامات',
                'description' => 'يُستخدم لعدة رياضات',
                'image' => null,
            ],
        ];

        foreach ($data as $item) {
            StadiumType::create($item);
        }
    }
}
