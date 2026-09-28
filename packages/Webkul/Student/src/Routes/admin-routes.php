<?php

use Illuminate\Support\Facades\Route;
use Webkul\Student\Http\Controllers\Admin\StudentController;

Route::group([
    'prefix' => config('app.admin_path'),
    'middleware' => ['web', 'admin_locale', 'user'],
], function () {
    Route::group(['prefix' => 'students'], function () {
        Route::get('', [StudentController::class, 'index'])->name('admin.students.index');
        Route::get('search', [StudentController::class, 'search'])->name('admin.students.search');
        Route::get('create', [StudentController::class, 'create'])->name('admin.students.create');
        Route::post('create', [StudentController::class, 'store'])->name('admin.students.store');
        Route::get('view/{id}', [StudentController::class, 'show'])->name('admin.students.view');
        Route::get('edit/{id}', [StudentController::class, 'edit'])->name('admin.students.edit');
        Route::put('edit/{id}', [StudentController::class, 'update'])->name('admin.students.update');
        Route::delete('{id}', [StudentController::class, 'destroy'])->name('admin.students.delete');
        Route::post('mass-delete', [StudentController::class, 'massDestroy'])->name('admin.students.mass_delete');
    });
});
