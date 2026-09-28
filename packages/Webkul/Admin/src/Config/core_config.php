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
    ],
];
