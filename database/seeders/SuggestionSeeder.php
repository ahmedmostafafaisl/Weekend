<?php

namespace Database\Seeders;

use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuggestionSeeder extends Seeder
{
    public function run(): void
    {

        $users = User::all();
        $data = [
            [
                'user_id' => $users->random()->id,
                'content' => 'تحسين نظام إضاءة الملعب',
            ],
            [
                'user_id' => $users->random()->id,
                'content' => 'إضافة مساحات جلوس أكثر',
            ],
            [
                'user_id' => $users->random()->id,
                'content' => 'تنظيم أفضل لمواقف السيارات',
            ],
            [
                'user_id' => $users->random()->id,
                'content' => 'تطوير نظام الصوت',
            ],
            [
                'user_id' => $users->random()->id,
                'content' => 'المزيد من خيارات الطعام والمشروبات',
            ],
        ];

        foreach ($data as $item) {
            Suggestion::create($item);
        }
    }
}
