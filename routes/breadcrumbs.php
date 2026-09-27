<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

// Dashboard
Breadcrumbs::for('dashboard', function (BreadcrumbTrail $trail) {
    $trail->push(trans('admin::app.layouts.dashboard'), route('admin.dashboard.index'));
});

// Settings
Breadcrumbs::for('settings', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(trans('admin::app.layouts.settings'), route('admin.settings.index'));
});

// Settings > Groups
Breadcrumbs::for('settings.groups', function (BreadcrumbTrail $trail) {
    $trail->parent('settings');
    $trail->push(trans('admin::app.layouts.groups'), route('admin.settings.groups.index'));
});

// Settings > Roles
Breadcrumbs::for('settings.roles', function (BreadcrumbTrail $trail) {
    $trail->parent('settings');
    $trail->push(trans('admin::app.layouts.roles'), route('admin.settings.roles.index'));
});

// Dashboard > Roles > Create Role
Breadcrumbs::for('settings.roles.create', function (BreadcrumbTrail $trail) {
    $trail->parent('settings.roles');
    $trail->push(trans('admin::app.settings.roles.create.title'), route('admin.settings.roles.create'));
});

// Dashboard > Roles > Edit Role
Breadcrumbs::for('settings.roles.edit', function (BreadcrumbTrail $trail, $role) {
    $trail->parent('settings.roles');
    $trail->push(trans('admin::app.settings.roles.edit.title'), route('admin.settings.roles.edit', $role->id));
});

// Settings > Users
Breadcrumbs::for('settings.users', function (BreadcrumbTrail $trail) {
    $trail->parent('settings');
    $trail->push(trans('admin::app.layouts.users'), route('admin.settings.users.index'));
});

// Dashboard > Users > Edit User
Breadcrumbs::for('settings.users.edit', function (BreadcrumbTrail $trail, $user) {
    $trail->parent('settings.users');
    $trail->push(trans('admin::app.settings.users.edit-title'), route('admin.settings.users.edit', $user->id));
});

// Settings > Student portal homepage
Breadcrumbs::for('settings.shop_theme', function (BreadcrumbTrail $trail) {
    $trail->parent('settings');
    $trail->push(trans('admin::app.settings.shop-theme.index.title'), route('admin.settings.shop-theme.index'));
});

Breadcrumbs::for('settings.shop_theme.edit', function (BreadcrumbTrail $trail, $theme) {
    $trail->parent('settings.shop_theme');
    $id = is_object($theme) ? $theme->id : $theme;
    $trail->push(trans('admin::app.settings.shop-theme.edit.title'), route('admin.settings.shop-theme.edit', $id));
});

// Configuration
Breadcrumbs::for('configuration', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(trans('admin::app.layouts.configuration'), route('admin.configuration.index'));
});

// Configuration > Config
Breadcrumbs::for('configuration.slug', function (BreadcrumbTrail $trail, $slug) {
    $trail->parent('configuration');
    $trail->push('', route('admin.configuration.index', ['slug' => $slug]));
});

// Dashboard > Account > Edit
Breadcrumbs::for('dashboard.account.edit', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(trans('admin::app.account.edit.title'), route('admin.user.account.edit'));
});

// Events (Top Level)
Breadcrumbs::for('events', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(trans('admin::app.events.title'), route('admin.events.index'));
});

// Events > Create
Breadcrumbs::for('events.create', function (BreadcrumbTrail $trail) {
    $trail->parent('events');
    $trail->push(trans('admin::app.events.create.title'), route('admin.events.create'));
});

// Events > Edit
Breadcrumbs::for('events.edit', function (BreadcrumbTrail $trail, $event) {
    $trail->parent('events');
    $trail->push(trans('admin::app.events.edit.title'), route('admin.events.edit', $event->id));
});

// Event Categories
Breadcrumbs::for('categories', function (BreadcrumbTrail $trail) {
    $trail->parent('events');
    $trail->push(trans('admin::app.event-categories.title'), route('admin.events.categories.index'));
});

// Event Categories > Create
Breadcrumbs::for('categories.create', function (BreadcrumbTrail $trail) {
    $trail->parent('categories');
    $trail->push(trans('admin::app.event-categories.create.title'), route('admin.events.categories.create'));
});

// Event Categories > Edit
Breadcrumbs::for('categories.edit', function (BreadcrumbTrail $trail, $category) {
    $trail->parent('categories');
    $trail->push(trans('admin::app.event-categories.edit.title'), route('admin.events.categories.edit', $category->id));
});

// Students (Top Level)
Breadcrumbs::for('students', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(trans('admin::app.students.title'), route('admin.students.index'));
});

// Students > Create
Breadcrumbs::for('students.create', function (BreadcrumbTrail $trail) {
    $trail->parent('students');
    $trail->push(trans('admin::app.students.create.title'), route('admin.students.create'));
});

// Students > Edit
Breadcrumbs::for('students.edit', function (BreadcrumbTrail $trail, $student) {
    $trail->parent('students');
    $trail->push(trans('admin::app.students.edit.title'), route('admin.students.edit', $student->id));
});

// Students > View
Breadcrumbs::for('students.view', function (BreadcrumbTrail $trail, $student) {
    $trail->parent('students');
    $trail->push('#'.$student->id, route('admin.students.view', $student->id));
});
