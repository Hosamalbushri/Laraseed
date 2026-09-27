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
        'key' => 'events',
        'name' => 'admin::app.acl.events',
        'route' => ['admin.events.index', 'admin.events.search'],
        'sort' => 2,
    ], [
        'key' => 'events.create',
        'name' => 'admin::app.acl.create',
        'route' => ['admin.events.create', 'admin.events.store'],
        'sort' => 1,
    ], [
        'key' => 'events.edit',
        'name' => 'admin::app.acl.edit',
        'route' => ['admin.events.edit', 'admin.events.update'],
        'sort' => 2,
    ], [
        'key' => 'events.delete',
        'name' => 'admin::app.acl.delete',
        'route' => 'admin.events.delete',
        'sort' => 3,
    ], [
        'key' => 'events.categories',
        'name' => 'admin::app.acl.event-categories',
        'route' => ['admin.events.categories.index', 'admin.events.categories.tree'],
        'sort' => 4,
    ], [
        'key' => 'events.categories.create',
        'name' => 'admin::app.acl.create',
        'route' => ['admin.events.categories.create', 'admin.events.categories.store'],
        'sort' => 1,
    ], [
        'key' => 'events.categories.edit',
        'name' => 'admin::app.acl.edit',
        'route' => ['admin.events.categories.edit', 'admin.events.categories.update'],
        'sort' => 2,
    ], [
        'key' => 'events.categories.delete',
        'name' => 'admin::app.acl.delete',
        'route' => 'admin.events.categories.delete',
        'sort' => 3,
    ], [
        'key' => 'students',
        'name' => 'admin::app.acl.students',
        'route' => ['admin.students.index', 'admin.students.search'],
        'sort' => 3,
    ], [
        'key' => 'students.create',
        'name' => 'admin::app.acl.create',
        'route' => ['admin.students.create', 'admin.students.store'],
        'sort' => 1,
    ], [
        'key' => 'students.edit',
        'name' => 'admin::app.acl.edit',
        'route' => ['admin.students.edit', 'admin.students.update'],
        'sort' => 2,
    ], [
        'key' => 'students.view',
        'name' => 'admin::app.acl.view',
        'route' => 'admin.students.view',
        'sort' => 3,
    ], [
        'key' => 'students.delete',
        'name' => 'admin::app.acl.delete',
        'route' => ['admin.students.delete', 'admin.students.mass_delete'],
        'sort' => 4,
    ], [
        'key' => 'students.manage-subscriptions',
        'name' => 'admin::app.acl.manage-subscriptions',
        'route' => ['admin.students.subscriptions.store', 'admin.students.subscriptions.delete'],
        'sort' => 5,
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
        'key' => 'settings.shop_theme',
        'name' => 'admin::app.settings.shop-theme.acl.title',
        'route' => 'admin.settings.shop-theme.index',
        'sort' => 3,
    ], [
        'key' => 'settings.shop_theme.homepage',
        'name' => 'admin::app.settings.shop-theme.acl.homepage',
        'route' => [
            'admin.settings.shop-theme.index',
            'admin.settings.shop-theme.edit',
            'admin.settings.shop-theme.update',
            'admin.settings.shop-theme.store',
            'admin.settings.shop-theme.destroy',
        ],
        'sort' => 1,
    ], [
        'key' => 'settings.shop_theme.create',
        'name' => 'admin::app.acl.create',
        'route' => 'admin.settings.shop-theme.store',
        'sort' => 1,
    ], [
        'key' => 'settings.shop_theme.edit',
        'name' => 'admin::app.acl.edit',
        'route' => ['admin.settings.shop-theme.edit', 'admin.settings.shop-theme.update'],
        'sort' => 2,
    ], [
        'key' => 'settings.shop_theme.delete',
        'name' => 'admin::app.acl.delete',
        'route' => 'admin.settings.shop-theme.destroy',
        'sort' => 3,
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
    ], [
        'key' => 'lost_found',
        'name' => 'Lost & Found Management',
        'route' => 'admin.lost_found.index',
        'sort' => 6,
    ], [
        'key' => 'lost_found.items',
        'name' => 'Found Items Management',
        'route' => 'admin.lost_found.items.index',
        'sort' => 1,
    ], [
        'key' => 'lost_found.items.view',
        'name' => 'View Found Items',
        'route' => 'admin.lost_found.items.index',
        'sort' => 1,
    ], [
        'key' => 'lost_found.items.create',
        'name' => 'Create Found Items',
        'route' => ['admin.lost_found.items.create', 'admin.lost_found.items.store'],
        'sort' => 2,
    ], [
        'key' => 'lost_found.items.edit',
        'name' => 'Edit Found Items',
        'route' => ['admin.lost_found.items.edit', 'admin.lost_found.items.update', 'admin.lost_found.items.images.store'],
        'sort' => 3,
    ], [
        'key' => 'lost_found.claims',
        'name' => 'Claims Management',
        'route' => 'admin.lost_found.claims.index',
        'sort' => 2,
    ], [
        'key' => 'lost_found.claims.view',
        'name' => 'View Claims',
        'route' => 'admin.lost_found.claims.index',
        'sort' => 1,
    ], [
        'key' => 'lost_found.claims.review',
        'name' => 'Review Claims',
        'route' => ['admin.lost_found.claims.review', 'admin.lost_found.claims.update_review'],
        'sort' => 2,
    ], [
        'key' => 'lost_found.claims.approve',
        'name' => 'Approve Claims',
        'route' => 'admin.lost_found.claims.approve',
        'sort' => 3,
    ], [
        'key' => 'lost_found.claims.reject',
        'name' => 'Reject Claims',
        'route' => 'admin.lost_found.claims.reject',
        'sort' => 4,
    ], [
        'key' => 'lost_found.custody',
        'name' => 'Custody Management',
        'route' => 'admin.lost_found.custody.index',
        'sort' => 3,
    ], [
        'key' => 'lost_found.custody.manage',
        'name' => 'Manage Custody',
        'route' => ['admin.lost_found.custody.receive', 'admin.lost_found.custody.transfer', 'admin.lost_found.custody.move_storage'],
        'sort' => 1,
    ], [
        'key' => 'lost_found.handover',
        'name' => 'Handover Management',
        'route' => 'admin.lost_found.handover.index',
        'sort' => 4,
    ], [
        'key' => 'lost_found.handover.complete',
        'name' => 'Complete Handover',
        'route' => 'admin.lost_found.handover.complete',
        'sort' => 1,
    ], [
        'key' => 'lost_found.settings',
        'name' => 'Lost & Found Settings',
        'route' => 'admin.lost_found.settings.index',
        'sort' => 5,
    ], [
        'key' => 'lost_found.settings.categories',
        'name' => 'Manage Categories',
        'route' => ['admin.lost_found.settings.categories.index', 'admin.lost_found.settings.categories.store', 'admin.lost_found.settings.categories.update'],
        'sort' => 1,
    ],
];
