<?php

return [
    /**
     * Dashboard.
     */
    [
        'key' => 'dashboard',
        'name' => 'admin::app.layouts.dashboard',
        'route' => 'admin.dashboard.index',
        'sort' => 1,
        'icon-class' => 'icon-dashboard',
    ],

    /**
     * Events.
     */
    [
        'key' => 'events',
        'name' => 'admin::app.events.title',
        'route' => 'admin.events.index',
        'sort' => 6,
        'icon-class' => 'icon-calendar',
    ], [
        'key' => 'events.event',
        'name' => 'admin::app.events.title',
        'route' => 'admin.events.index',
        'sort' => 1,
        'icon-class' => '',
    ], [
        'key' => 'events.categories',
        'name' => 'admin::app.event-categories.title',
        'route' => 'admin.events.categories.index',
        'sort' => 2,
        'icon-class' => '',
    ],

    /**
     * Students.
     */
    [
        'key' => 'students',
        'name' => 'admin::app.students.title',
        'route' => 'admin.students.index',
        'sort' => 7,
        'icon-class' => 'icon-contact',
    ],

    /**
     * Settings.
     */
    [
        'key' => 'settings',
        'name' => 'admin::app.layouts.settings',
        'route' => 'admin.settings.index',
        'sort' => 8,
        'icon-class' => 'icon-setting',
    ], [
        'key' => 'settings.user',
        'name' => 'admin::app.layouts.user',
        'route' => 'admin.settings.groups.index',
        'info' => 'admin::app.layouts.user-info',
        'sort' => 1,
        'icon-class' => 'icon-settings-group',
    ], [
        'key' => 'settings.user.roles',
        'name' => 'admin::app.layouts.roles',
        'info' => 'admin::app.layouts.roles-info',
        'route' => 'admin.settings.roles.index',
        'sort' => 2,
        'icon-class' => 'icon-role',
    ], [
        'key' => 'settings.user.users',
        'name' => 'admin::app.layouts.users',
        'info' => 'admin::app.layouts.users-info',
        'route' => 'admin.settings.users.index',
        'sort' => 3,
        'icon-class' => 'icon-user',
    ], [
        'key' => 'settings.shop_theme',
        'name' => 'admin::app.settings.shop-theme.section-title',
        'route' => 'admin.settings.shop-theme.index',
        'info' => 'admin::app.settings.shop-theme.section-info',
        'sort' => 4,
        'icon-class' => 'icon-settings',
    ], [
        'key' => 'settings.shop_theme.homepage',
        'name' => 'admin::app.settings.shop-theme.index.title',
        'route' => 'admin.settings.shop-theme.index',
        'info' => 'admin::app.settings.shop-theme.index.info',
        'sort' => 1,
        'icon-class' => 'icon-setting',
    ],

    /**
     * Configuration.
     */
    [
        'key' => 'configuration',
        'name' => 'admin::app.layouts.configuration',
        'route' => 'admin.configuration.index',
        'sort' => 9,
        'icon-class' => 'icon-configuration',
    ],
];
