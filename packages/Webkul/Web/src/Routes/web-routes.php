<?php

use Illuminate\Support\Facades\Route;
use Webkul\Web\Http\Controllers\HomeController;
use Webkul\Web\Http\Controllers\LocaleController;

Route::middleware(['web', 'web_context'])->group(function () {
    Route::get('/', [HomeController::class, 'index'])
        ->name('web.home');

    Route::match(['get', 'post'], 'web/locale/{code}', [LocaleController::class, 'switch'])
        ->name('web.locale.switch');
});
