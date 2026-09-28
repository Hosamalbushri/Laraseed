<?php

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Webkul\Event\Http\Controllers\Web\EventController;

Route::group(['middleware' => ['web', 'web_context']], function () {
    Route::get('events', [EventController::class, 'index'])
        ->name('event.web.index');

    Route::get('events/{event}', [EventController::class, 'show'])
        // Concord globally binds {event} to an unscoped model. Public lookup must
        // remain repository-owned so unavailable records are indistinguishable.
        ->withoutMiddleware(SubstituteBindings::class)
        ->whereNumber('event')
        ->name('event.web.show');
});
