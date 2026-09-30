<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

$basePath = dirname(__DIR__, 2);
$publicPath = $basePath.'/public';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$staticFile = realpath($publicPath.$requestPath);

if ($requestPath !== '/' && $staticFile && str_starts_with($staticFile, realpath($publicPath)) && is_file($staticFile)) {
    return false;
}

define('LARAVEL_START', microtime(true));

require $basePath.'/vendor/autoload.php';

$app = require $basePath.'/bootstrap/app.php';

$app->booted(function () use ($basePath) {
    Route::get('favicon.ico', fn () => response('', 204));

    Route::get('_test/browser/public-web-components', function () use ($basePath) {
        return View::file($basePath.'/tests/Fixtures/views/web-component-showcase.blade.php');
    })->middleware(['web', 'web_context']);

    Route::get('_test/browser/set-locale-en', function () {
        session()->put('web_locale', 'en');

        return redirect('/_test/browser/public-web-components');
    })->middleware('web');

    Route::get('_test/browser/set-locale-ar', function () {
        session()->put('web_locale', 'ar');

        return redirect('/_test/browser/public-web-components');
    })->middleware('web');
});

$app->handleRequest(Request::capture());
