<?php

use Illuminate\Support\Facades\Route;
use Webkul\Website\Http\Controllers\AboutController;

Route::middleware(['web', 'web_context'])->group(function () {
    Route::get('about', [AboutController::class, 'index'])
        ->name('website.about');
});
