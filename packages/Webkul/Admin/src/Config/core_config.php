<?php

return [
    /**
     * General.
     */
    [
        'key' => 'general',
        'name' => 'admin::app.configuration.index.general.title',
        'info' => 'admin::app.configuration.index.general.info',
        'sort' => 1,
    ], [
        'key' => 'general.general',
        'name' => 'admin::app.configuration.index.general.general.title',
        'info' => 'admin::app.configuration.index.general.general.info',
        'icon' => 'icon-setting',
        'sort' => 1,
    ], [
        'key' => 'general.general.locale_settings',
        'name' => 'admin::app.configuration.index.general.general.locale-settings.title',
        'info' => 'admin::app.configuration.index.general.general.locale-settings.title-info',
        'sort' => 1,
        'fields' => [
            [
                'name' => 'locale',
                'title' => 'admin::app.configuration.index.general.general.locale-settings.title',
                'type' => 'select',
                'default' => 'en',
                'options' => 'Webkul\Core\Core@locales',
            ],
        ],
    ], [
        'key' => 'general.design',
        'name' => 'admin::app.configuration.index.general.design.title',
        'info' => 'admin::app.configuration.index.general.design.info',
        'icon' => 'icon-configuration',
        'sort' => 2,
    ], [
        'key' => 'general.design.admin_logo',
        'name' => 'admin::app.configuration.index.general.design.admin-logo.title',
        'info' => 'admin::app.configuration.index.general.design.admin-logo.title-info',
        'sort' => 1,
        'fields' => [
            [
                'name' => 'logo_image',
                'title' => 'admin::app.configuration.index.general.design.admin-logo.logo-image',
                'type' => 'image',
                'channel_based' => false,
                'validation' => 'mimes:bmp,jpeg,jpg,png,webp,svg',
            ], [
                'name' => 'favicon',
                'title' => 'admin::app.configuration.index.general.design.admin-logo.favicon',
                'type' => 'image',
                'channel_based' => false,
                'validation' => 'mimes:bmp,jpeg,jpg,png,webp,svg,ico',
            ],
        ],
    ], [
        'key' => 'general.store',
        'name' => 'admin::app.configuration.index.general.store.title',
        'info' => 'admin::app.configuration.index.general.store.info',
        'icon' => 'icon-configuration',
        'sort' => 3,
    ], [
        'key' => 'general.store.shop',
        'name' => 'admin::app.configuration.index.general.store.shop-logo.title',
        'info' => 'admin::app.configuration.index.general.store.shop-logo.title-info',
        'sort' => 1,
        'fields' => [
            [
                'name' => 'logo_image',
                'title' => 'admin::app.configuration.index.general.store.shop-logo.logo-image',
                'type' => 'image',
                'channel_based' => false,
                'validation' => 'mimes:bmp,jpeg,jpg,png,webp,svg',
            ], [
                'name' => 'favicon',
                'title' => 'admin::app.configuration.index.general.store.shop-logo.favicon',
                'type' => 'image',
                'channel_based' => false,
                'validation' => 'mimes:bmp,jpeg,jpg,png,webp,svg,ico',
            ], [
                'name' => 'primary_color',
                'title' => 'admin::app.configuration.index.general.store.shop-logo.primary-color',
                'type' => 'color',
                'default' => '#0284c7',
            ], [
                'name' => 'accent_color',
                'title' => 'admin::app.configuration.index.general.store.shop-logo.accent-color',
                'type' => 'color',
                'default' => '#0369a1',
            ], [
                'name' => 'icon_color',
                'title' => 'admin::app.configuration.index.general.store.shop-logo.icon-color',
                'type' => 'color',
                'default' => '#0369a1',
            ], [
                'name' => 'badge_color',
                'title' => 'admin::app.configuration.index.general.store.shop-logo.badge-color',
                'type' => 'color',
                'default' => '#0284c7',
            ], [
                'name' => 'header_middle_logo',
                'title' => 'admin::app.configuration.index.general.store.shop-logo.header-middle-logo',
                'type' => 'image',
                'channel_based' => false,
                'validation' => 'mimes:bmp,jpeg,jpg,png,webp,svg',
            ],
        ],
    ], [
        'key' => 'general.store.events_page',
        'name' => 'admin::app.configuration.index.general.store.events-page.title',
        'info' => 'admin::app.configuration.index.general.store.events-page.title-info',
        'sort' => 2,
        'fields' => [
            [
                'name' => 'heading',
                'title' => 'admin::app.configuration.index.general.store.events-page.heading',
                'type' => 'text',
                'default' => 'Events for students',
                'validation' => 'max:255',
            ], [
                'name' => 'description',
                'title' => 'admin::app.configuration.index.general.store.events-page.description',
                'type' => 'textarea',
                'default' => 'Browse published events you can attend or follow.',
            ], [
                'name' => 'per_page',
                'title' => 'admin::app.configuration.index.general.store.events-page.per-page',
                'type' => 'number',
                'default' => 12,
                'validation' => 'integer|min:1|max:48',
            ],
        ],
    ], [
        'key' => 'general.store.navigation',
        'name' => 'admin::app.configuration.index.general.store.navigation.title',
        'info' => 'admin::app.configuration.index.general.store.navigation.title-info',
        'sort' => 3,
        'fields' => [
            [
                'name' => 'show_home',
                'title' => 'admin::app.configuration.index.general.store.navigation.show-home',
                'type' => 'boolean',
                'default' => true,
            ], [
                'name' => 'home_label',
                'title' => 'admin::app.configuration.index.general.store.navigation.home-label',
                'type' => 'text',
                'default' => 'Home',
                'validation' => 'max:100',
            ], [
                'name' => 'show_events',
                'title' => 'admin::app.configuration.index.general.store.navigation.show-events',
                'type' => 'boolean',
                'default' => true,
            ], [
                'name' => 'events_label',
                'title' => 'admin::app.configuration.index.general.store.navigation.events-label',
                'type' => 'text',
                'default' => 'Events',
                'validation' => 'max:100',
            ], [
                'name' => 'custom_1_enabled',
                'title' => 'admin::app.configuration.index.general.store.navigation.custom-1-enabled',
                'type' => 'boolean',
                'default' => false,
            ], [
                'name' => 'custom_1_label',
                'title' => 'admin::app.configuration.index.general.store.navigation.custom-1-label',
                'type' => 'text',
                'validation' => 'max:100',
            ], [
                'name' => 'custom_1_url',
                'title' => 'admin::app.configuration.index.general.store.navigation.custom-1-url',
                'type' => 'text',
                'validation' => 'max:500',
            ], [
                'name' => 'custom_2_enabled',
                'title' => 'admin::app.configuration.index.general.store.navigation.custom-2-enabled',
                'type' => 'boolean',
                'default' => false,
            ], [
                'name' => 'custom_2_label',
                'title' => 'admin::app.configuration.index.general.store.navigation.custom-2-label',
                'type' => 'text',
                'validation' => 'max:100',
            ], [
                'name' => 'custom_2_url',
                'title' => 'admin::app.configuration.index.general.store.navigation.custom-2-url',
                'type' => 'text',
                'validation' => 'max:500',
            ],
        ],
    ], [
        'key' => 'general.store.student_login',
        'name' => 'admin::app.configuration.index.general.store.student-login.title',
        'info' => 'admin::app.configuration.index.general.store.student-login.title-info',
        'sort' => 4,
        'fields' => [
            [
                'name' => 'logo_image',
                'title' => 'admin::app.configuration.index.general.store.student-login.field-logo-image',
                'type' => 'image',
                'channel_based' => false,
                'validation' => 'mimes:bmp,jpeg,jpg,png,webp,svg',
            ], [
                'name' => 'primary_color',
                'title' => 'admin::app.configuration.index.general.store.student-login.field-primary-color',
                'type' => 'color',
                'default' => '#2563eb',
            ], [
                'name' => 'accent_color',
                'title' => 'admin::app.configuration.index.general.store.student-login.field-accent-color',
                'type' => 'color',
                'default' => '#7c3aed',
            ], [
                'name' => 'surface_start',
                'title' => 'admin::app.configuration.index.general.store.student-login.field-surface-start',
                'type' => 'color',
                'default' => '#f9fafb',
            ], [
                'name' => 'surface_end',
                'title' => 'admin::app.configuration.index.general.store.student-login.field-surface-end',
                'type' => 'color',
                'default' => '#ffffff',
            ], [
                'name' => 'panel_start',
                'title' => 'admin::app.configuration.index.general.store.student-login.field-panel-start',
                'type' => 'color',
                'default' => '#0f172a',
            ], [
                'name' => 'panel_end',
                'title' => 'admin::app.configuration.index.general.store.student-login.field-panel-end',
                'type' => 'color',
                'default' => '#1d4ed8',
            ], [
                'name' => 'title',
                'title' => 'admin::app.configuration.index.general.store.student-login.field-title',
                'type' => 'text',
                'validation' => 'max:255',
            ], [
                'name' => 'description',
                'title' => 'admin::app.configuration.index.general.store.student-login.field-description',
                'type' => 'textarea',
            ], [
                'name' => 'eyebrow',
                'title' => 'admin::app.configuration.index.general.store.student-login.field-eyebrow',
                'type' => 'text',
                'validation' => 'max:100',
            ], [
                'name' => 'panel_lead',
                'title' => 'admin::app.configuration.index.general.store.student-login.field-panel-lead',
                'type' => 'textarea',
            ], [
                'name' => 'card_number',
                'title' => 'admin::app.configuration.index.general.store.student-login.field-card-number',
                'type' => 'text',
                'validation' => 'max:255',
            ], [
                'name' => 'password',
                'title' => 'admin::app.configuration.index.general.store.student-login.field-password',
                'type' => 'text',
                'validation' => 'max:255',
            ], [
                'name' => 'remember',
                'title' => 'admin::app.configuration.index.general.store.student-login.field-remember',
                'type' => 'text',
                'validation' => 'max:255',
            ], [
                'name' => 'submit',
                'title' => 'admin::app.configuration.index.general.store.student-login.field-submit',
                'type' => 'text',
                'validation' => 'max:255',
            ], [
                'name' => 'back_portal',
                'title' => 'admin::app.configuration.index.general.store.student-login.field-back-portal',
                'type' => 'text',
                'validation' => 'max:255',
            ],
        ],
    ], [
        'key' => 'general.settings',
        'name' => 'admin::app.configuration.index.general.settings.title',
        'info' => 'admin::app.configuration.index.general.settings.info',
        'icon' => 'icon-configuration',
        'sort' => 4,
    ], [
        'key' => 'general.settings.footer',
        'name' => 'admin::app.configuration.index.general.settings.footer.title',
        'info' => 'admin::app.configuration.index.general.settings.footer.info',
        'sort' => 1,
        'fields' => [
            [
                'name' => 'label',
                'title' => 'admin::app.configuration.index.general.settings.footer.powered-by',
                'type' => 'editor',
                'default' => 'Powered by <span style="color: rgb(14, 144, 217);">CampusHub</span>.',
                'tinymce' => true,
            ],
        ],
    ], [
        'key' => 'general.settings.menu',
        'name' => 'admin::app.configuration.index.general.settings.menu.title',
        'info' => 'admin::app.configuration.index.general.settings.menu.info',
        'sort' => 2,
        'fields' => [
            [
                'name' => 'dashboard',
                'title' => 'admin::app.configuration.index.general.settings.menu.dashboard',
                'type' => 'text',
                'default' => 'Dashboard',
                'validation' => 'max:20',
            ], [
                'name' => 'events',
                'title' => 'admin::app.configuration.index.general.settings.menu.events',
                'type' => 'text',
                'default' => 'Events',
                'validation' => 'max:20',
            ], [
                'name' => 'events.event',
                'title' => 'admin::app.configuration.index.general.settings.menu.events-tab',
                'type' => 'text',
                'default' => 'Events',
                'validation' => 'max:20',
            ], [
                'name' => 'events.categories',
                'title' => 'admin::app.configuration.index.general.settings.menu.categories',
                'type' => 'text',
                'default' => 'Categories',
                'validation' => 'max:30',
            ], [
                'name' => 'students',
                'title' => 'admin::app.configuration.index.general.settings.menu.students',
                'type' => 'text',
                'default' => 'Students',
                'validation' => 'max:20',
            ], [
                'name' => 'settings',
                'title' => 'admin::app.configuration.index.general.settings.menu.settings',
                'type' => 'text',
                'default' => 'Settings',
                'validation' => 'max:20',
            ], [
                'name' => 'configuration',
                'title' => 'admin::app.configuration.index.general.settings.menu.configuration',
                'type' => 'text',
                'default' => 'Configuration',
                'validation' => 'max:20',
            ],
        ],
    ], [
        'key' => 'general.settings.menu_color',
        'name' => 'admin::app.configuration.index.general.settings.menu-color.title',
        'info' => 'admin::app.configuration.index.general.settings.menu-color.info',
        'sort' => 3,
        'fields' => [
            [
                'name' => 'brand_color',
                'title' => 'admin::app.configuration.index.general.settings.menu-color.brand-color',
                'type' => 'color',
                'default' => '#0E90D9',
            ],
        ],
    ], [
        'key' => 'general.university_api',
        'name' => 'admin::app.configuration.index.general.university-api.title',
        'info' => 'admin::app.configuration.index.general.university-api.info',
        'icon' => 'icon-configuration',
        'sort' => 5,
    ], [
        'key' => 'general.university_api.endpoint_settings',
        'name' => 'admin::app.configuration.index.general.university-api.endpoint-settings.title',
        'info' => 'admin::app.configuration.index.general.university-api.endpoint-settings.info',
        'sort' => 1,
        'fields' => [
            [
                'name' => 'endpoint',
                'title' => 'admin::app.configuration.index.general.university-api.endpoint-settings.endpoint',
                'info' => 'admin::app.configuration.index.general.university-api.endpoint-settings.endpoint-info',
                'type' => 'text',
                'default' => 'https://api.university.example/students/verify',
            ],
        ],
    ],
];
