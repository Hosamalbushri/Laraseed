<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Webkul\Core\Contracts\AuthenticationRedirectResolver;
use Webkul\Installer\Http\Middleware\CanInstall;

// Register dynamic PSR-4 classloader mapping for local optional packages (persists across config:cache)
(function () {
    $basePath = dirname(__DIR__);
    $packagesRoot = realpath($basePath . DIRECTORY_SEPARATOR . 'packages');
    if ($packagesRoot === false) {
        return;
    }

    $composerLoader = null;
    foreach (spl_autoload_functions() as $func) {
        if (is_array($func) && isset($func[0]) && $func[0] instanceof \Composer\Autoload\ClassLoader) {
            $composerLoader = $func[0];
            break;
        }
    }

    if ($composerLoader === null) {
        return;
    }

    $boundaryPrefix = rtrim($packagesRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $manifests = glob($basePath . '/packages/*/*/composer.json') ?: [];

    foreach ($manifests as $manifestPath) {
        $realManifest = realpath($manifestPath);
        if ($realManifest === false || ! str_starts_with($realManifest, $boundaryPrefix)) {
            continue;
        }

        $raw = @file_get_contents($realManifest);
        if ($raw === false) {
            continue;
        }

        $data = json_decode($raw, true);
        if (! is_array($data) || ($data['extra']['laraseed']['type'] ?? null) !== 'optional') {
            continue;
        }

        $psr4 = $data['autoload']['psr-4'] ?? [];
        if (is_array($psr4)) {
            $pkgDir = dirname($realManifest);
            $existing = $composerLoader->getPrefixesPsr4();
            foreach ($psr4 as $prefix => $relSrc) {
                if (! is_string($prefix) || ! is_string($relSrc)) {
                    continue;
                }
                $targetSrc = realpath($pkgDir . DIRECTORY_SEPARATOR . trim($relSrc, '/\\'));
                if ($targetSrc !== false && str_starts_with($targetSrc, $boundaryPrefix) && is_dir($targetSrc)) {
                    $registered = $existing[$prefix] ?? [];
                    if (! in_array($targetSrc, $registered, true)) {
                        $composerLoader->addPsr4($prefix, $targetSrc);
                        $existing[$prefix][] = $targetSrc;
                    }
                }
            }
        }
    }
})();

return Application::configure(basePath: dirname(__DIR__))

    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(
            fn (Request $request) => app(AuthenticationRedirectResolver::class)->resolve($request)
        );

        $middleware->append(CanInstall::class);

        $middleware->encryptCookies(except: [
            'dark_mode',
        ]);

        $middleware->validateCsrfTokens(except: [
            'admin/mail/inbound-parse',
            'admin/web-forms/forms/*',
        ]);

        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
