<?php

/**
 * Canonical list of Saudi Arabia cities — single source of truth shared by:
 *   - GET /api/saudi-cities (public reference endpoint)
 *   - StoreUniteRequest's city validation (keeps venue city values controlled
 *     and consistent instead of free text buried inside location_name)
 *   - The admin/provider dashboard's city <select> dropdown on the unite
 *     create/edit form
 *
 * Keyed by a stable English slug (stored on unites.city) with an Arabic
 * label for display — this way the STORED value never changes even if the
 * active locale does, only how it's presented.
 */
return [
    ['key' => 'riyadh', 'label_ar' => 'الرياض', 'label_en' => 'Riyadh'],
    ['key' => 'jeddah', 'label_ar' => 'جدة', 'label_en' => 'Jeddah'],
    ['key' => 'makkah', 'label_ar' => 'مكة المكرمة', 'label_en' => 'Makkah'],
    ['key' => 'madinah', 'label_ar' => 'المدينة المنورة', 'label_en' => 'Madinah'],
    ['key' => 'dammam', 'label_ar' => 'الدمام', 'label_en' => 'Dammam'],
    ['key' => 'khobar', 'label_ar' => 'الخبر', 'label_en' => 'Khobar'],
    ['key' => 'dhahran', 'label_ar' => 'الظهران', 'label_en' => 'Dhahran'],
    ['key' => 'al_ahsa', 'label_ar' => 'الأحساء', 'label_en' => 'Al-Ahsa'],
    ['key' => 'taif', 'label_ar' => 'الطائف', 'label_en' => 'Taif'],
    ['key' => 'buraidah', 'label_ar' => 'بريدة', 'label_en' => 'Buraidah'],
    ['key' => 'tabuk', 'label_ar' => 'تبوك', 'label_en' => 'Tabuk'],
    ['key' => 'khamis_mushait', 'label_ar' => 'خميس مشيط', 'label_en' => 'Khamis Mushait'],
    ['key' => 'abha', 'label_ar' => 'أبها', 'label_en' => 'Abha'],
    ['key' => 'najran', 'label_ar' => 'نجران', 'label_en' => 'Najran'],
    ['key' => 'jazan', 'label_ar' => 'جازان', 'label_en' => 'Jazan'],
    ['key' => 'yanbu', 'label_ar' => 'ينبع', 'label_en' => 'Yanbu'],
    ['key' => 'qatif', 'label_ar' => 'القطيف', 'label_en' => 'Qatif'],
    ['key' => 'unaizah', 'label_ar' => 'عنيزة', 'label_en' => 'Unaizah'],
    ['key' => 'hail', 'label_ar' => 'حائل', 'label_en' => 'Hail'],
    ['key' => 'jubail', 'label_ar' => 'الجبيل', 'label_en' => 'Jubail'],
    ['key' => 'al_kharj', 'label_ar' => 'الخرج', 'label_en' => 'Al-Kharj'],
    ['key' => 'sakaka', 'label_ar' => 'سكاكا', 'label_en' => 'Sakaka'],
    ['key' => 'arar', 'label_ar' => 'عرعر', 'label_en' => 'Arar'],
    ['key' => 'bisha', 'label_ar' => 'بيشة', 'label_en' => 'Bisha'],
    ['key' => 'rabigh', 'label_ar' => 'رابغ', 'label_en' => 'Rabigh'],
    ['key' => 'qurayyat', 'label_ar' => 'القريات', 'label_en' => 'Qurayyat'],
    ['key' => 'al_baha', 'label_ar' => 'الباحة', 'label_en' => 'Al-Baha'],
    ['key' => 'ar_rass', 'label_ar' => 'الرس', 'label_en' => 'Ar-Rass'],
    ['key' => 'wadi_dawasir', 'label_ar' => 'وادي الدواسر', 'label_en' => 'Wadi Al-Dawasir'],
    ['key' => 'muhayil_asir', 'label_ar' => 'محايل عسير', 'label_en' => 'Muhayil Asir'],
    ['key' => 'sabya', 'label_ar' => 'صبيا', 'label_en' => 'Sabya'],
    ['key' => 'al_lith', 'label_ar' => 'الليث', 'label_en' => 'Al-Lith'],
    ['key' => 'khafji', 'label_ar' => 'الخفجي', 'label_en' => 'Khafji'],
    ['key' => 'rafha', 'label_ar' => 'رفحاء', 'label_en' => 'Rafha'],
    ['key' => 'turaif', 'label_ar' => 'طريف', 'label_en' => 'Turaif'],
    ['key' => 'sharurah', 'label_ar' => 'شرورة', 'label_en' => 'Sharurah'],
    ['key' => 'al_darb', 'label_ar' => 'الدرب', 'label_en' => 'Al-Darb'],
    ['key' => 'az_zulfi', 'label_ar' => 'الزلفي', 'label_en' => 'Az-Zulfi'],
];
