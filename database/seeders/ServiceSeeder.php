<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceGroup;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'bathSupplies' => [
                'label' => 'مستلزمات الحمام',
                'items' => ['صابون', 'مناديل', 'شامبو', 'جاكوزي', 'حوض استحمام', 'رداء حمام', 'دش', 'مجفف شعر', 'فرشاة أسنان', 'مناشف'],
            ],
            'kitchenSupplies' => [
                'label' => 'مستلزمات المطبخ',
                'items' => ['ثلاجة', 'فريزر', 'غلاية', 'فرن', 'غاز', 'مايكروويف', 'ماكينة قهوة', 'أواني طبخ'],
            ],
            'generalSupplies' => [
                'label' => 'مستلزمات عامة',
                'items' => ['انترنت', 'صالة طعام', 'غرفة سينما', 'بيت شعر', 'إطلالة', 'تلفزيون', 'مسبح مشترك', 'ركن شواء', 'ركن قهوة', 'شاطئ خاص', 'حفرة مندي', 'موقد حطب', 'مسطحات خضراء', 'جلسات خارجية', 'مرشات رذاذ', 'اشتراك مباريات', 'ملعب (قدم، طائرة، سلة)', 'Xbox', 'بلايستيشن', 'طاولة تنس', 'بلياردو', 'ألعاب أطفال خارجية', 'فرفيرة', 'نطيطة', 'ساونا', 'سماعات', 'بروجكتر', 'مدخل سيارات'],
            ],
            'poolSupplies' => [
                'label' => 'مستلزمات المسبح',
                'items' => ['مسبح داخلي', 'مسبح خارجي', 'مسبح بألعاب مائية', 'يحتوي على حاجز', 'غير متدرج', 'متدرج'],
            ],
        ];

        $groupSort = 1;
        foreach ($data as $groupName => $groupData) {
            $group = ServiceGroup::updateOrCreate(
                ['name' => $groupName],
                [
                    'label' => $groupData['label'],
                    'status' => 'active',
                    'sort_order' => $groupSort++,
                ]
            );

            $serviceSort = 1;
            foreach ($groupData['items'] as $item) {
                Service::updateOrCreate(
                    [
                        'service_group_id' => $group->id,
                        'name' => $item,
                    ],
                    [
                        'status' => 'active',
                        'sort_order' => $serviceSort++,
                    ]
                );
            }
        }
    }
}
