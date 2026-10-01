<?php

use Illuminate\Support\Facades\Route;
use Laraseed\Contacts\Admin\Http\Controllers\ContactController;

Route::prefix('contacts')->group(function () {
    Route::get('', [ContactController::class, 'index'])->name('admin.contacts.index');
    Route::get('create', [ContactController::class, 'create'])->name('admin.contacts.create');
    Route::post('create', [ContactController::class, 'store'])->name('admin.contacts.store');
    Route::get('view/{id}', [ContactController::class, 'show'])->name('admin.contacts.show');
    Route::get('edit/{id}', [ContactController::class, 'edit'])->name('admin.contacts.edit');
    Route::put('edit/{id}', [ContactController::class, 'update'])->name('admin.contacts.update');
    Route::delete('delete/{id}', [ContactController::class, 'destroy'])->name('admin.contacts.delete');
    Route::delete('{id}', [ContactController::class, 'destroy'])->name('admin.contacts.destroy');
    Route::post('mass-destroy', [ContactController::class, 'massDestroy'])->name('admin.contacts.mass_destroy');
    Route::post('mass-update', [ContactController::class, 'massUpdate'])->name('admin.contacts.mass_update');
});
