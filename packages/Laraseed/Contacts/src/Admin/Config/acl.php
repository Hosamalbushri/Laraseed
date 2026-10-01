<?php

return [
    [
        'key'   => 'contacts',
        'name'  => 'contacts_admin::app.acl.contacts',
        'route' => ['admin.contacts.index', 'admin.contacts.show'],
        'sort'  => 4,
    ],
    [
        'key'   => 'contacts.create',
        'name'  => 'contacts_admin::app.acl.create',
        'route' => ['admin.contacts.create', 'admin.contacts.store'],
        'sort'  => 1,
    ],
    [
        'key'   => 'contacts.edit',
        'name'  => 'contacts_admin::app.acl.edit',
        'route' => ['admin.contacts.edit', 'admin.contacts.update', 'admin.contacts.mass_update'],
        'sort'  => 2,
    ],
    [
        'key'   => 'contacts.delete',
        'name'  => 'contacts_admin::app.acl.delete',
        'route' => ['admin.contacts.delete', 'admin.contacts.destroy', 'admin.contacts.mass_destroy'],
        'sort'  => 3,
    ],
];
