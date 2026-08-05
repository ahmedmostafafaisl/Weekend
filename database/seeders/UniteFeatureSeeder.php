<?php

namespace Database\Seeders;

use App\Models\Unite;
use App\Models\UniteFeature;
use Illuminate\Database\Seeder;

class UniteFeatureSeeder extends Seeder
{
    public function run(): void
    {
        $featuresByType = [
            'stadium' => [
                ['name' => 'أضواء كشافة', 'description' => 'إضاءة LED كاملة التغطية للمباريات المسائية.'],
                ['name' => 'غرف تبديل الملابس', 'description' => 'غرفتان منفصلتان لتبديل الملابس مع دشّات.'],
                ['name' => 'مدرجات المتفرجين', 'description' => 'مدرجات مغطاة على أحد الجانبين.'],
                ['name' => 'محطة إسعافات أولية', 'description' => 'حقيبة إسعافات أولية وطاقم مدرّب عند الطلب.'],
                ['name' => 'موقف سيارات', 'description' => 'موقف مخصص يستوعب حتى 40 سيارة.'],
            ],
            'hall' => [
                ['name' => 'مسرح ومنصة', 'description' => 'مسرح مرتفع مع منصة، مناسب للخطابات والعروض.'],
                ['name' => 'نظام صوتي', 'description' => 'نظام مايكروفونات لاسلكية محترف.'],
                ['name' => 'جناح العروس', 'description' => 'غرفة تجهيز خاصة بمرايا ومقاعد.'],
                ['name' => 'خدمة صف السيارات', 'description' => 'خدمة صف سيارات متوفرة عند الطلب.'],
                ['name' => 'تكييف مركزي', 'description' => 'تكييف مركزي في جميع أجزاء القاعة.'],
            ],
            'lounge' => [
                ['name' => 'مسبح خاص', 'description' => 'مسبح خاص مُدفّأ مع مقاعد جانبية.'],
                ['name' => 'مطبخ مجهز بالكامل', 'description' => 'مطبخ يشمل ثلاجة وموقد وأدوات طبخ.'],
                ['name' => 'شاشة ذكية وواي فاي', 'description' => 'واي فاي عالي السرعة وشاشة ذكية كبيرة في الصالة الرئيسية.'],
                ['name' => 'منطقة شواء', 'description' => 'منطقة شواء خارجية مع مقاعد.'],
                ['name' => 'حديقة', 'description' => 'حديقة منسقة للاستراحة الخارجية.'],
            ],
            'camp' => [
                ['name' => 'حفرة نار', 'description' => 'حفرة نار تقليدية مع مقاعد حولها.'],
                ['name' => 'جلسة تراثية', 'description' => 'جلسة أرضية على طراز المجلس مع مساند.'],
                ['name' => 'مولد كهرباء', 'description' => 'مولد احتياطي لضمان استمرار الكهرباء.'],
                ['name' => 'خزان مياه', 'description' => 'خزان مياه في الموقع لمرافق الغسيل.'],
                ['name' => 'منصة مراقبة النجوم', 'description' => 'منصة مفتوحة بعيدة عن التلوث الضوئي.'],
            ],
        ];

        Unite::with('department')->get()->each(function (Unite $unite) use ($featuresByType) {
            $pool = $featuresByType[$unite->type] ?? $featuresByType['hall'];
            // Pick 3–5 features per venue, mostly active with an occasional inactive one.
            $count = rand(3, 5);
            $selected = collect($pool)->shuffle()->take($count);

            foreach ($selected as $i => $feature) {
                UniteFeature::updateOrCreate(
                    ['unite_id' => $unite->id, 'name' => $feature['name']],
                    [
                        'description' => $feature['description'],
                        'status' => $i === 0 && rand(1, 5) === 1 ? 'inactive' : 'active',
                    ]
                );
            }
        });
    }
}
