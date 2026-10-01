<?php

use Illuminate\Support\Facades\Route;
use Laraseed\Contacts\Http\Controllers\ContactController;

Route::group(['prefix' => 'api/contacts', 'middleware' => ['api', 'auth:sanctum']], function () {
    Route::get('', [ContactController::class, 'index'])->name('contacts.api.index');
    Route::post('', [ContactController::class, 'store'])->name('contacts.api.store');
    Route::get('{id}', [ContactController::class, 'show'])->name('contacts.api.show');
    Route::match(['put', 'patch'], '{id}', [ContactController::class, 'update'])->name('contacts.api.update');
    Route::delete('{id}', [ContactController::class, 'destroy'])->name('contacts.api.destroy');
});
