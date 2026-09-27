<?php

use Illuminate\Support\Facades\Route;
use Webkul\Student\Http\Controllers\StudentSessionController;

Route::prefix('student')->group(function () {
    Route::middleware('guest:student')->group(function () {
        Route::get('login', [StudentSessionController::class, 'create'])
            ->name('student.login');

        Route::post('login', [StudentSessionController::class, 'store'])
            ->middleware('throttle:student-login')
            ->name('student.login.store');
    });

    Route::post('logout', [StudentSessionController::class, 'destroy'])
        ->name('student.logout');
});
