<?php

return [
    [
        'key' => 'students',
        'name' => 'student::app.acl.students',
        'route' => ['admin.students.index', 'admin.students.search'],
        'sort' => 3,
    ], [
        'key' => 'students.create',
        'name' => 'student::app.acl.create',
        'route' => ['admin.students.create', 'admin.students.store'],
        'sort' => 1,
    ], [
        'key' => 'students.edit',
        'name' => 'student::app.acl.edit',
        'route' => ['admin.students.edit', 'admin.students.update'],
        'sort' => 2,
    ], [
        'key' => 'students.view',
        'name' => 'student::app.acl.view',
        'route' => 'admin.students.view',
        'sort' => 3,
    ], [
        'key' => 'students.delete',
        'name' => 'student::app.acl.delete',
        'route' => ['admin.students.delete', 'admin.students.mass_delete'],
        'sort' => 4,
    ],
];
