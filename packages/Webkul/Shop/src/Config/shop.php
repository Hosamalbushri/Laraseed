<?php

return [

    /**
     * Storefront theme code (Bagisto theme_code). Sections in the DB are filtered by this value.
     */
    'storefront_theme_code' => env('SHOP_STOREFRONT_THEME_CODE', 'default'),

    /**
     * Labels for theme_code (admin UI).
     */
    'theme_definitions' => [
        'default' => [
            'name' => 'Default',
        ],
    ],

    'logo_url' => null,

    /**
     * Student sign-in URL (path e.g. /student/login or full URL). Override with SHOP_STUDENT_LOGIN_URL if needed.
     */
    'student_login_url' => env('SHOP_STUDENT_LOGIN_URL', '/student/login'),

    /**
     * Home page SEO (Bagisto-style channel home_seo). Null values fall back to lang files.
     */
    'home_seo' => [
        'meta_title' => env('SHOP_HOME_META_TITLE'),
        'meta_description' => env('SHOP_HOME_META_DESCRIPTION'),
        'meta_keywords' => env('SHOP_HOME_META_KEYWORDS'),
    ],

    /**
     * Home page sections (same idea as Bagisto theme customizations: type + options, ordered).
     * Types: image_carousel, static_content, event_carousel, category_carousel (event categories), footer_links, services_content, immersive_hero. portal_footer is global (layout), not a home section. product_carousel is ignored on the storefront.
     */
    'home_customizations' => [
        [
            'sort_order' => 1,
            'status' => 1,
            'type' => 'static_content',
            'options' => [
                'view' => 'shop::home.partials.hero',
            ],
        ],
        [
            'sort_order' => 2,
            'status' => 1,
            'type' => 'event_carousel',
            'options' => [
                'title' => 'shop::app.home.event-carousel.title',
                'limit' => 8,
            ],
        ],
    ],

    'footer_sections' => [
        [
            ['title' => 'shop::app.components.layouts.footer.link-home', 'route' => 'shop.home.index'],
            ['title' => 'shop::app.components.layouts.footer.link-events', 'route' => 'shop.events.index'],
        ],
        [
            ['title' => 'shop::app.components.layouts.footer.link-student-login', 'student_login' => true],
        ],
    ],

    'services' => [
        [
            'service_icon' => 'icon-calendar',
            'title' => 'shop::app.components.layouts.services.calendar-title',
            'description' => 'shop::app.components.layouts.services.calendar-desc',
        ],
        [
            'service_icon' => 'icon-email',
            'title' => 'shop::app.components.layouts.services.updates-title',
            'description' => 'shop::app.components.layouts.services.updates-desc',
        ],
        [
            'service_icon' => 'icon-location',
            'title' => 'shop::app.components.layouts.services.campus-title',
            'description' => 'shop::app.components.layouts.services.campus-desc',
        ],
    ],
];
