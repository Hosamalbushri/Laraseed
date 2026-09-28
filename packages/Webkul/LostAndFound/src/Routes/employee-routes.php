<?php

use Illuminate\Support\Facades\Route;
use Webkul\LostAndFound\Http\Controllers\Employee\EmployeeClaimController;
use Webkul\LostAndFound\Http\Controllers\Employee\EmployeeClaimReadController;
use Webkul\LostAndFound\Http\Controllers\Employee\EmployeeCustodyController;
use Webkul\LostAndFound\Http\Controllers\Employee\EmployeeFoundItemController;
use Webkul\LostAndFound\Http\Controllers\Employee\EmployeeHandoverController;
use Webkul\LostAndFound\Http\Controllers\Employee\EmployeeItemReadController;

Route::prefix(config('app.admin_path').'/lost-found')
    ->middleware(['web', 'admin_locale', 'user'])
    ->group(function () {
        Route::get('items', [EmployeeItemReadController::class, 'index'])
            ->name('admin.lost_found.items.index');

        // Found Items Management
        Route::post('items', [EmployeeFoundItemController::class, 'store'])
            ->name('admin.lost_found.items.store');

        Route::put('items/{id}', [EmployeeFoundItemController::class, 'update'])
            ->name('admin.lost_found.items.update');

        Route::post('items/{id}/images', [EmployeeFoundItemController::class, 'uploadImage'])
            ->name('admin.lost_found.items.images.store');

        // Custody Management
        Route::post('items/{id}/custody/receive', [EmployeeCustodyController::class, 'receive'])
            ->name('admin.lost_found.custody.receive');

        Route::post('items/{id}/custody/transfer', [EmployeeCustodyController::class, 'transfer'])
            ->name('admin.lost_found.custody.transfer');

        Route::post('items/{id}/custody/move-storage', [EmployeeCustodyController::class, 'moveStorage'])
            ->name('admin.lost_found.custody.move_storage');

        // Physical Handover
        Route::post('items/{id}/handover', [EmployeeHandoverController::class, 'complete'])
            ->name('admin.lost_found.handover.complete');

        // Claims Management
        Route::get('items/{id}/claims', [EmployeeClaimReadController::class, 'index'])
            ->name('admin.lost_found.items.claims.index');

        Route::get('claims/{id}', [EmployeeClaimReadController::class, 'show'])
            ->name('admin.lost_found.claims.show');

        Route::post('claims/{id}/review', [EmployeeClaimController::class, 'review'])
            ->name('admin.lost_found.claims.review');

        Route::post('claims/{id}/approve', [EmployeeClaimController::class, 'approve'])
            ->name('admin.lost_found.claims.approve');

        Route::post('claims/{id}/reject', [EmployeeClaimController::class, 'reject'])
            ->name('admin.lost_found.claims.reject');

        Route::post('claims/{id}/revoke', [EmployeeClaimController::class, 'revoke'])
            ->name('admin.lost_found.claims.revoke');
    });
