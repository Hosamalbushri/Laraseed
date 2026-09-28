<?php

return [
    [
        'key' => 'events',
        'name' => 'event::app.acl.events',
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
        'name' => 'event::app.acl.event-categories',
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
    ],
    [
        'key' => 'students.manage-subscriptions',
        'name' => 'event::app.acl.manage-subscriptions',
        'route' => ['admin.students.subscriptions.store', 'admin.students.subscriptions.delete'],
        'sort' => 5,
    ],
];
