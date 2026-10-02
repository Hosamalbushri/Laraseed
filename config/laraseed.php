<?php

if (! class_exists(\Webkul\Core\Packages\OptionalPackageManifestLoader::class, false)) {
    $candidates = array_filter([
        (getenv('TESTBENCH_WORKING_PATH') ? rtrim(getenv('TESTBENCH_WORKING_PATH'), '/') . '/tests/helpers.php' : null),
        dirname(__DIR__, 4) . '/tests/helpers.php',
        dirname(__DIR__, 2) . '/tests/helpers.php',
        __DIR__ . '/../../tests/helpers.php',
    ]);
    foreach ($candidates as $cand) {
        if ($cand && file_exists($cand)) {
            require_once $cand;
            break;
        }
    }
}

$enabledStr = env('LARASEED_OPTIONAL_PACKAGES', '');
$enabledList = array_values(array_filter(array_map('trim', explode(',', (string) $enabledStr)), fn ($v) => $v !== ''));

$packagesDir = base_path('packages');
$manifestPaths = [];
if (is_dir($packagesDir)) {
    $composerLoader = null;
    foreach (spl_autoload_functions() as $autoloader) {
        if (is_array($autoloader) && $autoloader[0] instanceof \Composer\Autoload\ClassLoader) {
            $composerLoader = $autoloader[0];
            break;
        }
    }

    if ($composerLoader !== null) {
        $composerLoader->addPsr4('App\\', base_path('app'));
        $prefixes = $composerLoader->getPrefixesPsr4();
        $directories = glob($packagesDir . '/*/*', GLOB_ONLYDIR) ?: [];
        foreach ($directories as $dir) {
            $manifestPath = $dir . '/composer.json';
            if (file_exists($manifestPath)) {
                $manifest = json_decode(file_get_contents($manifestPath), true);
                if (is_array($manifest)) {
                    $psr4 = $manifest['autoload']['psr-4'] ?? [];
                    if (is_array($psr4)) {
                        foreach ($psr4 as $prefix => $relativePaths) {
                            $paths = is_array($relativePaths) ? $relativePaths : [$relativePaths];
                            foreach ($paths as $relPath) {
                                $targetDir = realpath($dir . '/' . trim($relPath, '/\\'));
                                if ($targetDir && str_starts_with($targetDir, realpath($packagesDir))) {
                                    $existingPaths = $prefixes[$prefix] ?? [];
                                    if (! in_array($targetDir, $existingPaths, true)) {
                                        $composerLoader->addPsr4($prefix, $targetDir);
                                    }
                                }
                            }
                        }
                    }

                    // Check if package is an optional package declaring extra.laraseed
                    $pkgMeta = $manifest['extra']['laraseed'] ?? null;
                    if (is_array($pkgMeta) && ($pkgMeta['type'] ?? null) === 'optional') {
                        $pkgId = $pkgMeta['id'] ?? null;
                        $providerClass = $pkgMeta['provider'] ?? null;
                        if ($pkgId && in_array($pkgId, $enabledList, true) && $providerClass && ! class_exists($providerClass)) {
                            throw new \RuntimeException(sprintf(
                                'Optional package [%s] declares an invalid provider class [%s].',
                                $pkgId,
                                $providerClass
                            ));
                        }

                        $manifestPaths[] = $manifestPath;
                    }
                }
            }
        }
    }
}

$catalog = (new \Webkul\Core\Packages\OptionalPackageManifestLoader)->load($manifestPaths);
$knownEnabled = array_values(array_filter($enabledList, fn ($id) => isset($catalog[$id])));
$composition = new \Webkul\Core\Packages\OptionalPackageComposition($catalog, $knownEnabled);

return [
    'default_web_package' => env('LARASEED_DEFAULT_WEB_PACKAGE', null),
    'web' => [
        'default_package' => env('LARASEED_DEFAULT_WEB_PACKAGE', null),
    ],
    'optional_packages' => [
        'enabled' => $composition->enabledPackages(),
        'catalog' => $composition->packages(),
        'providers' => array_values(array_unique(array_merge(
            $composition->providers(),
            $composition->capabilityProviders('web'),
        ))),
        'concord_modules' => $composition->concordModules(),
        'dependencies' => $composition->dependencyGraph(),
    ],
];