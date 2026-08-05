<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    | BUG FIX: this file was previously a byte-identical copy of the English
    | default — every one of Laravel's ~100 built-in validation rule messages
    | (required, min, max, unique, email, array, between, etc.) was rendering
    | in raw English across the ENTIRE application whenever the Arabic locale
    | was active, for any field that didn't have an explicit custom message
    | override. This is the genuine Arabic translation.
    |
    */

    'accepted' => 'يجب قبول :attribute.',
    'accepted_if' => 'يجب قبول :attribute عندما تكون :other هي :value.',
    'active_url' => ':attribute ليس رابطًا صالحًا.',
    'after' => 'يجب أن يكون :attribute تاريخًا بعد :date.',
    'after_or_equal' => 'يجب أن يكون :attribute تاريخًا بعد أو يساوي :date.',
    'alpha' => 'يجب أن يحتوي :attribute على حروف فقط.',
    'alpha_dash' => 'يجب أن يحتوي :attribute على حروف وأرقام وشرطات وشرطات سفلية فقط.',
    'alpha_num' => 'يجب أن يحتوي :attribute على حروف وأرقام فقط.',
    'array' => 'يجب أن يكون :attribute مصفوفة.',
    'before' => 'يجب أن يكون :attribute تاريخًا قبل :date.',
    'before_or_equal' => 'يجب أن يكون :attribute تاريخًا قبل أو يساوي :date.',
    'between' => [
        'array' => 'يجب أن يحتوي :attribute على عدد عناصر بين :min و :max.',
        'file' => 'يجب أن يكون حجم :attribute بين :min و :max كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute بين :min و :max.',
        'string' => 'يجب أن يحتوي :attribute على عدد أحرف بين :min و :max.',
    ],
    'boolean' => 'يجب أن تكون قيمة حقل :attribute صحيحة أو خاطئة.',
    'confirmed' => 'تأكيد :attribute غير مطابق.',
    'current_password' => 'كلمة المرور غير صحيحة.',
    'date' => ':attribute ليس تاريخًا صالحًا.',
    'date_equals' => 'يجب أن يكون :attribute تاريخًا يساوي :date.',
    'date_format' => ':attribute لا يطابق الصيغة :format.',
    'declined' => 'يجب رفض :attribute.',
    'declined_if' => 'يجب رفض :attribute عندما تكون :other هي :value.',
    'different' => 'يجب أن يكون :attribute و :other مختلفين.',
    'digits' => 'يجب أن يكون :attribute مكوّنًا من :digits أرقام.',
    'digits_between' => 'يجب أن يكون :attribute بين :min و :max أرقام.',
    'dimensions' => 'أبعاد صورة :attribute غير صالحة.',
    'distinct' => 'حقل :attribute يحتوي على قيمة مكررة.',
    'doesnt_end_with' => 'لا يجوز أن ينتهي :attribute بأحد القيم التالية: :values.',
    'doesnt_start_with' => 'لا يجوز أن يبدأ :attribute بأحد القيم التالية: :values.',
    'email' => 'يجب أن يكون :attribute بريدًا إلكترونيًا صالحًا.',
    'ends_with' => 'يجب أن ينتهي :attribute بأحد القيم التالية: :values.',
    'enum' => 'القيمة المحددة لـ :attribute غير صالحة.',
    'exists' => 'القيمة المحددة لـ :attribute غير صالحة.',
    'file' => 'يجب أن يكون :attribute ملفًا.',
    'filled' => 'يجب أن يحتوي حقل :attribute على قيمة.',
    'gt' => [
        'array' => 'يجب أن يحتوي :attribute على أكثر من :value عناصر.',
        'file' => 'يجب أن يكون حجم :attribute أكبر من :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute أكبر من :value.',
        'string' => 'يجب أن يحتوي :attribute على أكثر من :value أحرف.',
    ],
    'gte' => [
        'array' => 'يجب أن يحتوي :attribute على :value عناصر أو أكثر.',
        'file' => 'يجب أن يكون حجم :attribute أكبر من أو يساوي :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute أكبر من أو تساوي :value.',
        'string' => 'يجب أن يحتوي :attribute على :value أحرف أو أكثر.',
    ],
    'image' => 'يجب أن يكون :attribute صورة.',
    'in' => 'القيمة المحددة لـ :attribute غير صالحة.',
    'in_array' => 'حقل :attribute غير موجود ضمن :other.',
    'integer' => 'يجب أن يكون :attribute عددًا صحيحًا.',
    'ip' => 'يجب أن يكون :attribute عنوان IP صالحًا.',
    'ipv4' => 'يجب أن يكون :attribute عنوان IPv4 صالحًا.',
    'ipv6' => 'يجب أن يكون :attribute عنوان IPv6 صالحًا.',
    'json' => 'يجب أن يكون :attribute نص JSON صالحًا.',
    'lt' => [
        'array' => 'يجب أن يحتوي :attribute على أقل من :value عناصر.',
        'file' => 'يجب أن يكون حجم :attribute أقل من :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute أقل من :value.',
        'string' => 'يجب أن يحتوي :attribute على أقل من :value أحرف.',
    ],
    'lte' => [
        'array' => 'يجب ألا يحتوي :attribute على أكثر من :value عناصر.',
        'file' => 'يجب أن يكون حجم :attribute أقل من أو يساوي :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute أقل من أو تساوي :value.',
        'string' => 'يجب أن يحتوي :attribute على :value أحرف أو أقل.',
    ],
    'mac_address' => 'يجب أن يكون :attribute عنوان MAC صالحًا.',
    'max' => [
        'array' => 'يجب ألا يحتوي :attribute على أكثر من :max عناصر.',
        'file' => 'يجب ألا يزيد حجم :attribute عن :max كيلوبايت.',
        'numeric' => 'يجب ألا تزيد قيمة :attribute عن :max.',
        'string' => 'يجب ألا يزيد :attribute عن :max أحرف.',
    ],
    'max_digits' => 'يجب ألا يحتوي :attribute على أكثر من :max أرقام.',
    'mimes' => 'يجب أن يكون :attribute ملفًا من نوع: :values.',
    'mimetypes' => 'يجب أن يكون :attribute ملفًا من نوع: :values.',
    'min' => [
        'array' => 'يجب أن يحتوي :attribute على :min عناصر على الأقل.',
        'file' => 'يجب أن يكون حجم :attribute :min كيلوبايت على الأقل.',
        'numeric' => 'يجب أن تكون قيمة :attribute :min على الأقل.',
        'string' => 'يجب أن يحتوي :attribute على :min أحرف على الأقل.',
    ],
    'min_digits' => 'يجب أن يحتوي :attribute على :min أرقام على الأقل.',
    'multiple_of' => 'يجب أن يكون :attribute من مضاعفات :value.',
    'not_in' => 'القيمة المحددة لـ :attribute غير صالحة.',
    'not_regex' => 'صيغة :attribute غير صالحة.',
    'numeric' => 'يجب أن يكون :attribute رقمًا.',
    'password' => [
        'letters' => 'يجب أن يحتوي :attribute على حرف واحد على الأقل.',
        'mixed' => 'يجب أن يحتوي :attribute على حرف كبير وحرف صغير على الأقل.',
        'numbers' => 'يجب أن يحتوي :attribute على رقم واحد على الأقل.',
        'symbols' => 'يجب أن يحتوي :attribute على رمز واحد على الأقل.',
        'uncompromised' => 'ظهرت قيمة :attribute هذه في تسريب بيانات سابق. يرجى اختيار :attribute آخر.',
    ],
    'present' => 'يجب أن يكون حقل :attribute موجودًا.',
    'prohibited' => 'حقل :attribute غير مسموح به.',
    'prohibited_if' => 'حقل :attribute غير مسموح به عندما تكون :other هي :value.',
    'prohibited_unless' => 'حقل :attribute غير مسموح به إلا إذا كانت :other ضمن :values.',
    'prohibits' => 'حقل :attribute يمنع وجود :other.',
    'regex' => 'صيغة :attribute غير صالحة.',
    'required' => 'حقل :attribute مطلوب.',
    'required_array_keys' => 'يجب أن يحتوي حقل :attribute على مدخلات لـ: :values.',
    'required_if' => 'حقل :attribute مطلوب عندما تكون :other هي :value.',
    'required_if_accepted' => 'حقل :attribute مطلوب عند قبول :other.',
    'required_unless' => 'حقل :attribute مطلوب إلا إذا كانت :other ضمن :values.',
    'required_with' => 'حقل :attribute مطلوب عند وجود :values.',
    'required_with_all' => 'حقل :attribute مطلوب عند وجود :values.',
    'required_without' => 'حقل :attribute مطلوب عند عدم وجود :values.',
    'required_without_all' => 'حقل :attribute مطلوب عند عدم وجود أي من :values.',
    'same' => 'يجب أن يتطابق :attribute و :other.',
    'size' => [
        'array' => 'يجب أن يحتوي :attribute على :size عناصر.',
        'file' => 'يجب أن يكون حجم :attribute :size كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute :size.',
        'string' => 'يجب أن يحتوي :attribute على :size أحرف.',
    ],
    'starts_with' => 'يجب أن يبدأ :attribute بأحد القيم التالية: :values.',
    'string' => 'يجب أن يكون :attribute نصًا.',
    'timezone' => 'يجب أن يكون :attribute نطاقًا زمنيًا صالحًا.',
    'unique' => 'قيمة :attribute مُستخدمة من قبل.',
    'uploaded' => 'فشل رفع :attribute.',
    'url' => 'يجب أن يكون :attribute رابطًا صالحًا.',
    'uuid' => 'يجب أن يكون :attribute معرّف UUID صالحًا.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    | BUG FIX: this was previously an empty array, so every raw field name
    | (department_id, lounge.customize_place, hall.max_capacity, etc.) was
    | shown as-is inside the :attribute placeholder above — Laravel only
    | replaces underscores with spaces, it doesn't translate or humanize
    | beyond that. Populated with Arabic labels for every field name used
    | across the app's FormRequest classes.
    |
    */

    'attributes' => [
        // ── Auth / Profile ──────────────────────────────────────────────────
        'name' => 'الاسم',
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',
        'current_password' => 'كلمة المرور الحالية',
        'phone' => 'رقم الهاتف',
        'type' => 'النوع',
        'status' => 'الحالة',
        'nation' => 'الجنسية',
        'id_number' => 'رقم الهوية',
        'birth_date' => 'تاريخ الميلاد',
        'photo' => 'الصورة الشخصية',
        'front_identity' => 'صورة الهوية الأمامية',
        'back_identity' => 'صورة الهوية الخلفية',
        'sak_image' => 'صورة الصك',
        'commercial_register_image' => 'صورة السجل التجاري',
        'commercial_register_number' => 'رقم السجل التجاري',
        'commercial_name' => 'الاسم التجاري',
        'organization_name' => 'اسم المنشأة',
        'provider_type' => 'نوع المزود',
        'ownership' => 'الملكية',
        'delegation' => 'التفويض',
        'remember' => 'تذكرني',
        'fcm_token' => 'رمز الإشعارات',

        // ── Department ────────────────────────────────────────────────────
        'department_id' => 'القسم',
        'location' => 'الموقع',
        'latitude' => 'خط العرض',
        'longitude' => 'خط الطول',
        'facebook' => 'فيسبوك',
        'twitter' => 'تويتر',
        'instagram' => 'إنستغرام',
        'youtube' => 'يوتيوب',
        'website' => 'الموقع الإلكتروني',
        'whatsapp' => 'واتساب',
        'snapchat' => 'سناب شات',
        'tiktok' => 'تيك توك',
        'images' => 'الصور',
        'images.*' => 'صورة',
        'keep_image_ids' => 'الصور المحتفظ بها',
        'keep_image_ids.*' => 'معرّف صورة',

        // ── Unite (venue) ─────────────────────────────────────────────────
        'unite_id' => 'الوحدة',
        'description' => 'الوصف',
        'location_name' => 'اسم الموقع',
        'reservation_deposit' => 'العربون',
        'reservation_deposit_type' => 'نوع العربون',
        'reservation_deposit_amount' => 'مبلغ العربون',
        'insurance' => 'التأمين',
        'insurance_amount' => 'مبلغ التأمين',
        'insurance_policy_id' => 'سياسة التأمين',
        'refund_policy' => 'سياسة الاسترجاع',
        'additional_terms' => 'الشروط الإضافية',
        'service_ids' => 'الخدمات',
        'service_ids.*' => 'خدمة',
        'families_and_singles' => 'العائلات والعازبين',

        // Stadium detail fields
        'stadium.customize_Category' => 'فئة الملعب',
        'stadium.customize_Place' => 'موقع الملعب',
        'stadium.width' => 'عرض الملعب',
        'stadium.length' => 'طول الملعب',
        'stadium.amenities' => 'المرافق',
        'stadium.cafeteria' => 'الكافتيريا',

        // Hall detail fields
        'hall.max_chairs' => 'الحد الأقصى للكراسي',
        'hall.max_tables' => 'الحد الأقصى للطاولات',
        'hall.max_capacity' => 'السعة القصوى',
        'hall.women_seating' => 'جلسة نسائية',
        'hall.kusha' => 'الكوشة',
        'hall.buffet' => 'البوفيه',
        'hall.buffet_details' => 'تفاصيل البوفيه',
        'hall.morning_start_time' => 'وقت بداية الفترة الصباحية',
        'hall.morning_end_time' => 'وقت نهاية الفترة الصباحية',
        'hall.evening_start_time' => 'وقت بداية الفترة المسائية',
        'hall.evening_end_time' => 'وقت نهاية الفترة المسائية',

        // Lounge detail fields
        'lounge.area' => 'مساحة الصالة',
        'lounge.customize_Place' => 'موقع الصالة',
        'lounge.bedroom' => 'غرف النوم',
        'lounge.bedroom_number' => 'عدد غرف النوم',
        'lounge.bathroom' => 'الحمامات',
        'lounge.bathroom_number' => 'عدد الحمامات',
        'lounge.pool' => 'المسبح',
        'lounge.kitchen' => 'المطبخ',
        'lounge.council' => 'المجلس',
        'lounge.television' => 'التلفاز',

        // Camp detail fields
        'camp.width' => 'عرض المخيم',
        'camp.length' => 'طول المخيم',
        'camp.seating_capacity' => 'سعة الجلوس',
        'camp.television' => 'التلفاز',
        'camp.fireplace' => 'موقد النار',
        'camp.bathroom' => 'الحمامات',
        'camp.bathroom_number' => 'عدد الحمامات',

        // Slots
        'slots' => 'الفترات',
        'slots.*.day_of_week' => 'يوم الأسبوع',
        'slots.*.morning_start' => 'بداية الفترة الصباحية',
        'slots.*.morning_end' => 'نهاية الفترة الصباحية',
        'slots.*.evening_start' => 'بداية الفترة المسائية',
        'slots.*.evening_end' => 'نهاية الفترة المسائية',
        'slots.*.full_start' => 'بداية اليوم الكامل',
        'slots.*.full_end' => 'نهاية اليوم الكامل',
        'slots.*.status' => 'حالة الفترة',

        // Prices
        'prices' => 'الأسعار',
        'prices.*.day' => 'اليوم',
        'prices.*.price' => 'السعر',
        'prices.*.morning_price' => 'سعر الفترة الصباحية',
        'prices.*.evening_price' => 'سعر الفترة المسائية',
        'prices.*.full_price' => 'سعر اليوم الكامل',
        'prices.*.hourly_enabled' => 'تفعيل الحجز بالساعة',
        'prices.*.day_hour_price' => 'سعر الساعة النهاري',
        'prices.*.night_hour_price' => 'سعر الساعة الليلي',
        'prices.*.day_start' => 'بداية الفترة النهارية',
        'prices.*.day_end' => 'نهاية الفترة النهارية',
        'prices.*.min_booking_minutes' => 'أقل مدة للحجز بالدقائق',

        // Features / offers / packages / new features
        'features' => 'المميزات',
        'features.*.name' => 'اسم الميزة',
        'features.*.description' => 'وصف الميزة',
        'features.*.status' => 'حالة الميزة',
        'offers' => 'العروض',
        'offers.*.name' => 'اسم العرض',
        'offers.*.start' => 'تاريخ بداية العرض',
        'offers.*.end' => 'تاريخ نهاية العرض',
        'offers.*.morning_price' => 'سعر العرض الصباحي',
        'offers.*.evening_price' => 'سعر العرض المسائي',
        'offers.*.full_day_price' => 'سعر العرض لليوم الكامل',
        'offers.*.status' => 'حالة العرض',
        'packages' => 'الباقات',
        'packages.*.name' => 'اسم الباقة',
        'packages.*.men_capacity' => 'سعة الرجال',
        'packages.*.women_capacity' => 'سعة النساء',
        'packages.*.price' => 'سعر الباقة',
        'new_features' => 'المميزات الجديدة',
        'new_features.*.title' => 'عنوان الميزة الجديدة',
        'new_features.*.description' => 'وصف الميزة الجديدة',

        // ── Reservations ──────────────────────────────────────────────────
        'reservation_date' => 'تاريخ الحجز',
        'period_type' => 'نوع الفترة',
        'from_time' => 'وقت البداية',
        'to_time' => 'وقت النهاية',
        'guest_count' => 'عدد الضيوف',
        'promo_code' => 'رمز الخصم',
        'notes' => 'الملاحظات',
        'payment_method' => 'طريقة الدفع',
        'reason' => 'السبب',

        // ── Payments ──────────────────────────────────────────────────────
        'amount' => 'المبلغ',
        'currency' => 'العملة',
        'reservation_id' => 'الحجز',
        'subscription_id' => 'الاشتراك',
        'quantity' => 'الكمية',
        'price' => 'السعر',
        'order_id' => 'رقم الطلب',
        'code' => 'الرمز',

        // ── Ads ───────────────────────────────────────────────────────────
        'title' => 'العنوان',
        'thumbnail' => 'الصورة المصغرة',
        'media' => 'الوسائط',
        'media.*' => 'ملف وسائط',
        'is_active' => 'مفعّل',
        'user_id' => 'المستخدم',
        'city' => 'المدينة',
        'target_audience' => 'الجمهور المستهدف',
        'target_user_type' => 'نوع المستخدم المستهدف',
        'target_users' => 'المستخدمون المستهدفون',
        'target_users.*' => 'مستخدم مستهدف',
        'body' => 'المحتوى',
        'content' => 'المحتوى',

        // ── Subscriptions / Packages ──────────────────────────────────────
        'package_id' => 'الباقة',
        'duration' => 'المدة',
        'count' => 'العدد',
        'percentage' => 'النسبة',

        // ── Roles / Permissions ────────────────────────────────────────────
        'permission_ids' => 'الصلاحيات',
        'permission_ids.*' => 'صلاحية',

        // ── Service groups / services ────────────────────────────────────
        'service_group_id' => 'مجموعة الخدمة',
        'label' => 'التسمية',
        'sort_order' => 'ترتيب الفرز',

        // ── Stadium types / Insurance policies / Suggestions ─────────────
        'image' => 'الصورة',

        // ── Transfers ─────────────────────────────────────────────────────
        'requested_amount' => 'المبلغ المطلوب',
        'preferred_method' => 'الطريقة المفضلة',
        'transfer_days' => 'أيام التحويل',
        'transfer_methods' => 'طرق التحويل',
        'tax_rate' => 'نسبة الضريبة',
        'platform_fee_rate' => 'نسبة رسوم المنصة',

        // ── Promo codes ───────────────────────────────────────────────────
        'discount_type' => 'نوع الخصم',
        'discount_value' => 'قيمة الخصم',
        'min_amount' => 'الحد الأدنى للمبلغ',
        'max_discount' => 'الحد الأقصى للخصم',
        'max_uses' => 'الحد الأقصى للاستخدام',
        'max_uses_per_user' => 'الحد الأقصى للاستخدام لكل مستخدم',
        'starts_at' => 'تاريخ البداية',
        'expires_at' => 'تاريخ الانتهاء',

        // ── Ratings ───────────────────────────────────────────────────────
        'rating' => 'التقييم',
        'review' => 'المراجعة',
    ],

];
