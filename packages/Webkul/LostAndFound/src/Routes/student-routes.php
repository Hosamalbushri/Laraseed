<?php

use Illuminate\Support\Facades\Route;
use Webkul\LostAndFound\Http\Controllers\Student\StudentClaimController;
use Webkul\LostAndFound\Http\Controllers\Student\StudentLostReportController;

Route::prefix('student/lost-found')
    ->middleware(['web', 'admin_locale', 'auth:student'])
    ->group(function () {
        Route::post('reports', [StudentLostReportController::class, 'store'])
            ->name('student.lost_found.reports.store');

        Route::put('reports/{id}', [StudentLostReportController::class, 'update'])
            ->name('student.lost_found.reports.update');

        Route::post('reports/{id}/images', [StudentLostReportController::class, 'uploadImage'])
            ->name('student.lost_found.reports.images.store');

        Route::post('claims', [StudentClaimController::class, 'store'])
            ->name('student.lost_found.claims.store');

        Route::post('claims/{id}/evidence', [StudentClaimController::class, 'addEvidence'])
            ->name('student.lost_found.claims.evidence.store');

        Route::post('claims/{id}/images', [StudentClaimController::class, 'uploadImage'])
            ->name('student.lost_found.claims.images.store');

        Route::post('claims/{id}/withdraw', [StudentClaimController::class, 'withdraw'])
            ->name('student.lost_found.claims.withdraw');
    });
