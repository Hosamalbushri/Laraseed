<?php

use Illuminate\Support\Facades\Route;
use Webkul\Web\Http\Controllers\LocaleController;

Route::middleware(['web', 'web_context'])->group(function () {
    Route::match(['get', 'post'], 'web/locale/{code}', [LocaleController::class, 'switch'])
        ->name('web.locale.switch');
});
