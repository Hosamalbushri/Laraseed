<?php

use Illuminate\Support\Facades\Route;
use Webkul\Shop\Http\Controllers\Student\StudentAccountController;
use Webkul\Shop\Http\Controllers\Student\StudentEventsController;

/**
 * Reserved for signed-in student area routes (accounts, events, etc.).
 */
Route::prefix('student')
    ->middleware(['auth:student'])
    ->group(function () {
        Route::get('account/edit', [StudentAccountController::class, 'edit'])
            ->name('shop.student.account.edit');

        Route::post('account/update', [StudentAccountController::class, 'update'])
            ->name('shop.student.account.update');

        Route::get('events', [StudentEventsController::class, 'index'])
            ->name('shop.student.events.index');
    });
