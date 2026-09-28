<?php

return [
    [
        'key' => 'dashboard',
        'name' => 'admin::app.layouts.dashboard',
        'route' => ['admin.dashboard.index', 'admin.dashboard.stats'],
        'sort' => 1,
    ], [
        'key' => 'content_upload',
        'name' => 'TinyMCE image upload',
        'route' => 'admin.tinymce.upload',
        'sort' => 6,
    ], [
        'key' => 'settings',
        'name' => 'admin::app.acl.settings',
        'route' => ['admin.settings.index', 'admin.settings.search'],
        'sort' => 4,
    ], [
        'key' => 'settings.user',
        'name' => 'admin::app.acl.user',
        'route' => ['admin.settings.groups.index', 'admin.settings.roles.index', 'admin.settings.users.index'],
        'sort' => 1,
    ], [
        'key' => 'settings.user.groups',
        'name' => 'admin::app.acl.groups',
        'route' => 'admin.settings.groups.index',
        'sort' => 1,
    ], [
        'key' => 'settings.user.groups.create',
        'name' => 'admin::app.acl.create',
        'route' => 'admin.settings.groups.store',
        'sort' => 1,
    ], [
        'key' => 'settings.user.groups.edit',
        'name' => 'admin::app.acl.edit',
        'route' => ['admin.settings.groups.edit', 'admin.settings.groups.update'],
        'sort' => 2,
    ], [
        'key' => 'settings.user.groups.delete',
        'name' => 'admin::app.acl.delete',
        'route' => 'admin.settings.groups.delete',
        'sort' => 3,
    ], [
        'key' => 'settings.user.roles',
        'name' => 'admin::app.acl.roles',
        'route' => 'admin.settings.roles.index',
        'sort' => 2,
    ], [
        'key' => 'settings.user.roles.create',
        'name' => 'admin::app.acl.create',
        'route' => ['admin.settings.roles.create', 'admin.settings.roles.store'],
        'sort' => 1,
    ], [
        'key' => 'settings.user.roles.edit',
        'name' => 'admin::app.acl.edit',
        'route' => ['admin.settings.roles.edit', 'admin.settings.roles.update'],
        'sort' => 2,
    ], [
        'key' => 'settings.user.roles.delete',
        'name' => 'admin::app.acl.delete',
        'route' => 'admin.settings.roles.delete',
        'sort' => 3,
    ], [
        'key' => 'settings.user.users',
        'name' => 'admin::app.acl.users',
        'route' => ['admin.settings.users.index', 'admin.settings.users.search'],
        'sort' => 3,
    ], [
        'key' => 'settings.user.users.create',
        'name' => 'admin::app.acl.create',
        'route' => 'admin.settings.users.store',
        'sort' => 1,
    ], [
        'key' => 'settings.user.users.edit',
        'name' => 'admin::app.acl.edit',
        'route' => ['admin.settings.users.edit', 'admin.settings.users.update', 'admin.settings.users.mass_update'],
        'sort' => 2,
    ], [
        'key' => 'settings.user.users.delete',
        'name' => 'admin::app.acl.delete',
        'route' => ['admin.settings.users.delete', 'admin.settings.users.mass_delete'],
        'sort' => 3,
    ], [
        'key' => 'settings.website_languages',
        'name' => 'admin::website-languages.title',
        'route' => 'admin.settings.website-languages.index',
        'sort' => 4,
    ], [
        'key' => 'settings.website_languages.overview',
        'name' => 'admin::website-languages.title',
        'route' => 'admin.settings.website-languages.index',
        'sort' => 1,
    ], [
        'key' => 'settings.website_languages.create',
        'name' => 'admin::app.acl.create',
        'route' => 'admin.settings.website-languages.store',
        'sort' => 2,
    ], [
        'key' => 'settings.website_languages.edit',
        'name' => 'admin::app.acl.edit',
        'route' => 'admin.settings.website-languages.update',
        'sort' => 3,
    ], [
        'key' => 'settings.website_languages.manage',
        'name' => 'admin::website-languages.manage',
        'route' => ['admin.settings.website-languages.activate', 'admin.settings.website-languages.deactivate', 'admin.settings.website-languages.primary'],
        'sort' => 4,
    ], [
        'key' => 'configuration',
        'name' => 'admin::app.acl.configuration',
        'route' => [
            'admin.configuration.index',
            'admin.configuration.store',
            'admin.configuration.search',
            'admin.configuration.download',
        ],
        'sort' => 5,
    ],
];
