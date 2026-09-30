<?php

use Illuminate\Support\Facades\Route;
use Webkul\Website\Integrations\LostAndFound\Http\Controllers\LostAndFoundController;

Route::middleware(['web', 'web_context'])->group(function () {
    Route::get('lost-found', [LostAndFoundController::class, 'index'])
        ->name('website.lost_found.index');

    Route::get('lost-found/{reference}', [LostAndFoundController::class, 'show'])
        ->name('website.lost_found.show');
});
