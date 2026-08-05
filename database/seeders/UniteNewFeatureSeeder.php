<?php

namespace Database\Seeders;

use App\Models\Unite;
use App\Models\UniteNewFeature;
use Illuminate\Database\Seeder;

class UniteNewFeatureSeeder extends Seeder
{
    public function run(): void
    {
        $highlightsByType = [
            'stadium' => [
                ['title' => 'أرضية ملعب مجددة', 'description' => ['عشب جديد بتركيب هذا الموسم', 'أبعاد بمعايير الفيفا', 'نظام صرف مياه محسّن']],
                ['title' => 'إضاءة مطوّرة', 'description' => ['أبراج إضاءة LED جديدة', 'أسطع بنسبة 30% من السابق', 'تصميم موفّر للطاقة']],
            ],
            'hall' => [
                ['title' => 'تجديد داخلي', 'description' => ['ثريات جديدة في كل الأماكن', 'طلاء وأرضيات جديدة', 'عزل صوتي محسّن']],
                ['title' => 'توسعة السعة', 'description' => ['تستوعب الآن 50 ضيفًا إضافيًا', 'منطقة جلوس إضافية', 'مدخل إضافي مُضاف']],
            ],
            'lounge' => [
                ['title' => 'رصيف مسبح جديد', 'description' => ['بلاط مسبح مُجدد', 'أثاث جديد بجانب المسبح', 'إضافة مناطق مظللة']],
                ['title' => 'تطوير المطبخ', 'description' => ['أجهزة جديدة مُركبة', 'مساحة عمل موسّعة', 'مساحة تخزين إضافية']],
            ],
            'camp' => [
                ['title' => 'تحسين المرافق', 'description' => ['وحدة حمامات متحركة جديدة', 'سعة خزان مياه محسّنة', 'إضاءة ليلية أفضل']],
                ['title' => 'توسعة الجلسات', 'description' => ['مساند مجلس إضافية', 'منطقة جلوس مظللة جديدة', 'حفرة نار إضافية']],
            ],
        ];

        Unite::all()->each(function (Unite $unite) use ($highlightsByType) {
            $pool = $highlightsByType[$unite->type] ?? $highlightsByType['hall'];

            // Not every venue has a "what's new" entry — roughly 60% do.
            if (rand(1, 10) > 6) {
                return;
            }

            $chosen = collect($pool)->random(rand(1, 2));
            foreach ($chosen as $item) {
                UniteNewFeature::updateOrCreate(
                    ['unite_id' => $unite->id, 'title' => $item['title']],
                    ['description' => $item['description']]
                );
            }
        });
    }
}
