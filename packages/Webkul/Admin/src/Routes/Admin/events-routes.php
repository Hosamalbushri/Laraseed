<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Events\EventCategoryController;
use Webkul\Admin\Http\Controllers\Events\EventController;

/**
 * Event routes.
 */
Route::group(['prefix' => 'events'], function () {
    Route::group(['prefix' => 'events'], function () {
        Route::get('', [EventController::class, 'index'])->name('admin.events.index');
        Route::get('create', [EventController::class, 'create'])->name('admin.events.create');
        Route::post('create', [EventController::class, 'store'])->name('admin.events.store');
        Route::get('edit/{id}', [EventController::class, 'edit'])->name('admin.events.edit');
        Route::put('edit/{id}', [EventController::class, 'update'])->name('admin.events.update');
        Route::get('search', [EventController::class, 'search'])->name('admin.events.search');
        Route::delete('{id}', [EventController::class, 'destroy'])->name('admin.events.delete');
    });

    // Categories
    Route::group(['prefix' => 'categories'], function () {
        Route::get('tree', [EventCategoryController::class, 'tree'])->name('admin.events.categories.tree');
        Route::get('', [EventCategoryController::class, 'index'])->name('admin.events.categories.index');
        Route::get('create', [EventCategoryController::class, 'create'])->name('admin.events.categories.create');
        Route::post('create', [EventCategoryController::class, 'store'])->name('admin.events.categories.store');
        Route::get('edit/{id}', [EventCategoryController::class, 'edit'])->name('admin.events.categories.edit');
        Route::put('edit/{id}', [EventCategoryController::class, 'update'])->name('admin.events.categories.update');
        Route::delete('{id}', [EventCategoryController::class, 'destroy'])->name('admin.events.categories.delete');
    });
});
