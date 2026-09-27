<?php

use Illuminate\Support\Facades\Route;
use Webkul\Shop\Http\Controllers\EventController;
use Webkul\Shop\Http\Controllers\EventSubscriptionController;
use Webkul\Shop\Http\Controllers\HomeController;

Route::get('/', [HomeController::class, 'index'])
    ->name('shop.home.index');

Route::get('events', [EventController::class, 'index'])
    ->name('shop.events.index');

Route::get('events/{id}', [EventController::class, 'show'])
    ->whereNumber('id')
    ->name('shop.events.show');

Route::post('events/{id}/subscribe', [EventSubscriptionController::class, 'store'])
    ->middleware(['auth:student', 'throttle:30,1'])
    ->whereNumber('id')
    ->name('shop.events.subscribe');

Route::post('events/{id}/unsubscribe', [EventSubscriptionController::class, 'destroy'])
    ->middleware(['auth:student', 'throttle:30,1'])
    ->whereNumber('id')
    ->name('shop.events.unsubscribe');
