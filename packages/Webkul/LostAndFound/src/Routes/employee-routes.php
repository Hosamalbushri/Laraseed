<?php

use Illuminate\Support\Facades\Route;
use Webkul\LostAndFound\Http\Controllers\Employee\EmployeeFoundItemController;

Route::prefix('admin/lost-found')
    ->middleware(['web', 'user'])
    ->group(function () {
        Route::post('items', [EmployeeFoundItemController::class, 'store'])
            ->name('admin.lost_found.items.store');

        Route::put('items/{id}', [EmployeeFoundItemController::class, 'update'])
            ->name('admin.lost_found.items.update');

        Route::post('items/{id}/images', [EmployeeFoundItemController::class, 'uploadImage'])
            ->name('admin.lost_found.items.images.store');
    });
