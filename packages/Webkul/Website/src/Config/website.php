<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Public Website Identity
    |--------------------------------------------------------------------------
    |
    | Site-specific institutional identity owned exclusively by Webkul\Website.
    | Values are keyed by active Web content locale code ('en', 'ar').
    |
    */
    'identity' => [
        'name' => [
            'en' => 'University CampusHub',
            'ar' => 'منصة الحرم الجامعي',
        ],

        'short_name' => [
            'en' => 'CampusHub',
            'ar' => 'الحرم الجامعي',
        ],

        'tagline' => [
            'en' => 'CampusHub Official Deployment',
            'ar' => 'منصة الحرم الجامعي الرسمية',
        ],

        'description' => [
            'en' => 'Your unified digital portal for campus life, student services, and academic resources.',
            'ar' => 'بوابتكم الرقمية الموحدة للحياة الجامعية والخدمات الطلابية والموارد الأكاديمية.',
        ],

        'about_heading' => [
            'en' => 'Empowering Future Leaders',
            'ar' => 'تمكين قادة المستقبل',
        ],

        'about_body' => [
            'en' => 'Our institution is dedicated to academic rigor, pioneering research, and fostering an inclusive community where students from diverse backgrounds learn, innovate, and lead.',
            'ar' => 'تلتزم مؤسستنا بالتميز الأكاديمي والبحث الرائد ورعاية مجتمع شامل يتعلم فيه الطلاب من مختلف الخلفيات ويبتكرون ويقودون.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Organization-Wide Public Contact Information
    |--------------------------------------------------------------------------
    |
    | General institutional contact details for public presentation (About page
    | and site footer). Feature-specific operational desks (such as Lost & Found
    | custody/security offices) remain in their respective integration boundaries.
    |
    */
    'contact' => [
        'email' => 'info@campushub.edu',

        'phone' => '+1 (555) 010-2000',

        'address' => [
            'en' => '100 University Avenue, Central Campus',
            'ar' => '100 شارع الجامعة، الحرم الجامعي المركزي',
        ],

        'office_hours' => [
            'en' => 'Sunday – Thursday, 8:00 AM – 4:00 PM',
            'ar' => 'الأحد – الخميس، 8:00 صباحاً – 4:00 مساءً',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Public Branding References
    |--------------------------------------------------------------------------
    |
    | Optional public asset URLs or relative public paths for logo and favicon.
    | Must never contain local filesystem paths or unsafe schemes.
    |
    */
    'branding' => [
        'logo_url' => null,

        'logo_alt' => [
            'en' => 'University CampusHub',
            'ar' => 'منصة الحرم الجامعي',
        ],

        'favicon_url' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Public SEO Defaults
    |--------------------------------------------------------------------------
    |
    | Site-level fallback metadata consumed by SeoService when individual pages
    | do not supply explicit overrides, plus the localized site title suffix.
    |
    */
    'seo' => [
        'site_name' => [
            'en' => 'CampusHub',
            'ar' => 'منصة الحرم الجامعي',
        ],

        'default_title' => [
            'en' => 'University CampusHub',
            'ar' => 'منصة الحرم الجامعي',
        ],

        'default_description' => [
            'en' => 'Your unified digital portal for campus life, student services, and academic resources.',
            'ar' => 'بوابتكم الرقمية الموحدة للحياة الجامعية والخدمات الطلابية والموارد الأكاديمية.',
        ],

        'default_image_url' => null,
    ],
];
