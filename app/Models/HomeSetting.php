<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeSetting extends Model
{
    protected $fillable = [
        'site_name',
        'logo_path',
        'logo_light_path',
        'app_image_path',
        'why_us_image_path',
        'hero_badge','hero_title','hero_subtitle','search_title','featured_title','featured_subtitle',
        'featured_unite_ids','featured_limit','show_search','show_categories','show_featured','show_stats','show_why_us','show_app_section',
        'app_title','app_text','google_play_url','apple_store_url','footer_text',
    ];

    protected $casts = [
        'featured_unite_ids' => 'array',
        'show_search' => 'boolean',
        'show_categories' => 'boolean',
        'show_featured' => 'boolean',
        'show_stats' => 'boolean',
        'show_why_us' => 'boolean',
        'show_app_section' => 'boolean',
        'featured_limit' => 'integer',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'site_name' => 'ويكند',
            'hero_badge' => 'ويكند — كل لحظة تستحقها',
            'hero_title' => 'أماكن تصنع لحظات لا تُنسى',
            'hero_subtitle' => 'اكتشف أفضل الملاعب والقاعات والاستراحات والمخيمات، وأكمل الحجز من تطبيق ويكند.',
            'search_title' => 'ابحث عن مكانك المثالي',
            'featured_title' => 'أماكن مميزة',
            'featured_subtitle' => 'اختيارات مميزة تناسب مناسبتك القادمة',
            'featured_limit' => 8,
            'show_search' => true,
            'show_categories' => true,
            'show_featured' => true,
            'show_stats' => true,
            'show_why_us' => true,
            'show_app_section' => true,
            'app_title' => 'ويكند معك أينما كنت',
            'app_text' => 'اكتشف أماكن جديدة، قارن الخيارات، وأكمل الحجز من تطبيق ويكند بخطوات بسيطة.',
            'footer_text' => 'منصة سعودية تجمع أفضل أماكن التجارب والمناسبات في مكان واحد.',
        ]);
    }
}
