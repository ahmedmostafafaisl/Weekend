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
        // Arabic text fields
        'hero_badge', 'hero_title', 'hero_subtitle', 'search_title', 'featured_title', 'featured_subtitle',
        // English text fields
        'hero_badge_en', 'hero_title_en', 'hero_subtitle_en', 'search_title_en', 'featured_title_en', 'featured_subtitle_en',
        'featured_unite_ids', 'featured_limit',
        'show_search', 'show_categories', 'show_featured', 'show_stats', 'show_why_us', 'show_app_section',
        // Arabic app/footer
        'app_title', 'app_text', 'footer_text',
        // English app/footer
        'app_title_en', 'app_text_en', 'footer_text_en',
        'google_play_url', 'apple_store_url',
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
            // Arabic defaults
            'hero_badge' => 'ويكند — كل لحظة تستحقها',
            'hero_title' => 'أماكن تصنع لحظات لا تُنسى',
            'hero_subtitle' => 'اكتشف أفضل الملاعب والقاعات والاستراحات والمخيمات، وأكمل الحجز من تطبيق ويكند.',
            'search_title' => 'ابحث عن مكانك المثالي',
            'featured_title' => 'أماكن مميزة',
            'featured_subtitle' => 'اختيارات مميزة تناسب مناسبتك القادمة',
            // English defaults
            'hero_badge_en' => 'Weekend — Every moment counts',
            'hero_title_en' => 'Places that make unforgettable moments',
            'hero_subtitle_en' => 'Discover the best stadiums, halls, lounges and camps, and complete your booking on the Weekend app.',
            'search_title_en' => 'Find your perfect venue',
            'featured_title_en' => 'Featured Venues',
            'featured_subtitle_en' => 'Handpicked selections for your next occasion',
            'featured_limit' => 8,
            'show_search' => true, 'show_categories' => true, 'show_featured' => true,
            'show_stats' => true, 'show_why_us' => true, 'show_app_section' => true,
            // Arabic app/footer
            'app_title' => 'ويكند معك أينما كنت',
            'app_text' => 'اكتشف أماكن جديدة، قارن الخيارات، وأكمل الحجز من تطبيق ويكند بخطوات بسيطة.',
            'footer_text' => 'منصة سعودية تجمع أفضل أماكن التجارب والمناسبات في مكان واحد.',
            // English app/footer
            'app_title_en' => 'Weekend, wherever you are',
            'app_text_en' => 'Discover new places, compare options, and book in minutes on the Weekend app.',
            'footer_text_en' => 'A Saudi platform bringing together the best experience and event venues in one place.',
        ]);
    }

    /**
     * Return the right-language value for a given field.
     * Arabic fields are the base name; English fields are <name>_en.
     * Falls back to Arabic when the EN value is empty.
     */
    public function trans(string $field): string
    {
        $locale = app()->getLocale();
        $enField = $field.'_en';

        if ($locale !== 'ar' && isset($this->attributes[$enField]) && $this->{$enField}) {
            return (string) $this->{$enField};
        }

        return (string) ($this->{$field} ?? '');
    }
}
