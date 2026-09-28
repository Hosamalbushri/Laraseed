<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

/**
 * Events breadcrumb trails.
 */
Breadcrumbs::for('events', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(trans('event::app.events.title'), route('admin.events.index'));
});

Breadcrumbs::for('events.create', function (BreadcrumbTrail $trail) {
    $trail->parent('events');
    $trail->push(trans('event::app.events.create.title'), route('admin.events.create'));
});

Breadcrumbs::for('events.edit', function (BreadcrumbTrail $trail, $event) {
    $trail->parent('events');
    $trail->push(trans('event::app.events.edit.title'), route('admin.events.edit', $event->id));
});

Breadcrumbs::for('categories', function (BreadcrumbTrail $trail) {
    $trail->parent('events');
    $trail->push(trans('event::app.event-categories.title'), route('admin.events.categories.index'));
});

Breadcrumbs::for('categories.create', function (BreadcrumbTrail $trail) {
    $trail->parent('categories');
    $trail->push(trans('event::app.event-categories.create.title'), route('admin.events.categories.create'));
});

Breadcrumbs::for('categories.edit', function (BreadcrumbTrail $trail, $category) {
    $trail->parent('categories');
    $trail->push(trans('event::app.event-categories.edit.title'), route('admin.events.categories.edit', $category->id));
});
