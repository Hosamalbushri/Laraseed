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
        'key' => 'settings.website_languages',
        'name' => 'admin::website-languages.title',
        'route' => 'admin.settings.website-languages.index',
        'info' => 'admin::website-languages.info',
        'sort' => 5,
        'icon-class' => 'icon-settings',
    ], [
        'key' => 'settings.website_languages.overview',
        'name' => 'admin::website-languages.title',
        'route' => 'admin.settings.website-languages.index',
        'info' => 'admin::website-languages.info',
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
