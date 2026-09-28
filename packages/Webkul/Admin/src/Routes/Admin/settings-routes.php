<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Settings\GroupController;
use Webkul\Admin\Http\Controllers\Settings\RoleController;
use Webkul\Admin\Http\Controllers\Settings\SettingController;
use Webkul\Admin\Http\Controllers\Settings\UserController;
use Webkul\Admin\Http\Controllers\Settings\WebsiteLanguageController;

/**
 * Settings routes (minimal: groups, roles, users + settings search).
 */
Route::prefix('settings')->group(function () {
    Route::controller(WebsiteLanguageController::class)->prefix('website-languages')->group(function () {
        Route::get('', 'index')->name('admin.settings.website-languages.index');
        Route::post('', 'store')->name('admin.settings.website-languages.store');
        Route::put('{id}', 'update')->name('admin.settings.website-languages.update');
        Route::post('{id}/activate', 'activate')->name('admin.settings.website-languages.activate');
        Route::post('{id}/deactivate', 'deactivate')->name('admin.settings.website-languages.deactivate');
        Route::post('{id}/primary', 'primary')->name('admin.settings.website-languages.primary');
    });
    /**
     * Settings routes.
     */
    Route::controller(SettingController::class)->prefix('settings')->group(function () {
        Route::get('', 'index')->name('admin.settings.index');

        Route::get('search', 'search')->name('admin.settings.search');
    });

    /**
     * Groups routes.
     */
    Route::controller(GroupController::class)->prefix('groups')->group(function () {
        Route::get('', 'index')->name('admin.settings.groups.index');

        Route::post('create', 'store')->name('admin.settings.groups.store');

        Route::get('edit/{id}', 'edit')->name('admin.settings.groups.edit');

        Route::put('edit/{id}', 'update')->name('admin.settings.groups.update');

        Route::delete('{id}', 'destroy')->name('admin.settings.groups.delete');
    });

    /**
     * Roles routes.
     */
    Route::controller(RoleController::class)->prefix('roles')->group(function () {
        Route::get('', 'index')->name('admin.settings.roles.index');

        Route::get('create', 'create')->name('admin.settings.roles.create');

        Route::post('create', 'store')->name('admin.settings.roles.store');

        Route::get('edit/{id}', 'edit')->name('admin.settings.roles.edit');

        Route::put('edit/{id}', 'update')->name('admin.settings.roles.update');

        Route::delete('{id}', 'destroy')->name('admin.settings.roles.delete');
    });

    /**
     * Users Routes.
     */
    Route::controller(UserController::class)->prefix('users')->group(function () {
        Route::get('', 'index')->name('admin.settings.users.index');

        Route::post('create', 'store')->name('admin.settings.users.store');

        Route::get('edit/{id?}', 'edit')->name('admin.settings.users.edit');

        Route::put('edit/{id}', 'update')->name('admin.settings.users.update');

        Route::get('search', 'search')->name('admin.settings.users.search');

        Route::delete('{id}', 'destroy')->name('admin.settings.users.delete');

        Route::post('mass-update', 'massUpdate')->name('admin.settings.users.mass_update');

        Route::post('mass-destroy', 'massDestroy')->name('admin.settings.users.mass_delete');
    });
});
