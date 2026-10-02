<?php

use Illuminate\Support\Facades\Route;

Route::get('admin/login', fn () => 'Admin Login')->name('admin.session.create');
Route::get('admin', fn () => redirect()->route('admin.session.create'))->name('admin');
Route::get('install', fn () => 'Installer')->name('installer.index');
Route::get('api/health', fn () => response()->json(['status' => 'ok']));
Route::get('up', fn () => response('OK', 200));

if (! config('laraseed.web.root_owner')) {
    Route::get('/', [\App\Http\Controllers\WebEntryPointController::class, 'index'])->name('laraseed.web.entry');
}