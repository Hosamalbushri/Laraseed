<?php

use Illuminate\Support\Facades\Route;

$adminPath = trim((string) config('app.admin_path', 'admin'), '/');

if ($adminPath !== 'admin' && $adminPath !== '') {
    Route::redirect('/admin', '/'.$adminPath, 302);
    Route::redirect('/admin/', '/'.$adminPath, 302);

    Route::get('/admin/{path?}', function (?string $path = null) use ($adminPath) {
        $target = '/'.$adminPath;

        if ($path !== null && $path !== '') {
            $target .= '/'.ltrim($path, '/');
        }

        return redirect($target, 302);
    })->where('path', '.*');
}
