<?php

use Composer\Autoload\ClassLoader;
use Webkul\Core\Packages\OptionalPackageComposition;
use Webkul\Core\Packages\OptionalPackageManifestLoader;

if (! class_exists(OptionalPackageManifestLoader::class, false)) {
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

// 1. Resolve application root and packages boundary for containment
$basePath = dirname(__DIR__);
$packagesRoot = realpath($basePath . DIRECTORY_SEPARATOR . 'packages');
if ($packagesRoot === false) {
    $packagesRoot = $basePath . DIRECTORY_SEPARATOR . 'packages';
}
$boundaryPrefix = rtrim($packagesRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

// 2. Locate active Composer ClassLoader
$composerLoader = null;
foreach (spl_autoload_functions() as $func) {
    if (is_array($func) && isset($func[0]) && $func[0] instanceof ClassLoader) {
        $composerLoader = $func[0];
        break;
    }
}

if ($composerLoader !== null && is_dir($basePath . '/app')) {
    $composerLoader->addPsr4('App\\', $basePath . '/app');
}

// 3. Discover, validate and dynamically map PSR-4 namespaces for local packages
$allManifestPaths = glob($basePath . '/packages/*/*/composer.json') ?: [];
$validManifestPaths = [];
$manifestDataMap = [];

foreach ($allManifestPaths as $manifestPath) {
    if (! is_file($manifestPath) || ! is_readable($manifestPath)) {
        continue;
    }

    // Security: Assert manifest resides strictly within authorized packages directory
    $realManifest = realpath($manifestPath);
    if ($realManifest === false || ! str_starts_with($realManifest, $boundaryPrefix)) {
        continue;
    }

    $raw = @file_get_contents($realManifest);
    if ($raw === false) {
        continue;
    }

    try {
        $data = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
    } catch (\JsonException) {
        continue;
    }

    if (! is_array($data) || ($data['extra']['laraseed']['type'] ?? null) !== 'optional') {
        continue;
    }

    // Register local PSR-4 mappings with Composer ClassLoader before class resolution
    $psr4 = $data['autoload']['psr-4'] ?? [];
    if (is_array($psr4) && $composerLoader !== null) {
        $pkgDir = dirname($realManifest);
        $prefixes = $composerLoader->getPrefixesPsr4();
        foreach ($psr4 as $prefix => $relSrc) {
            if (! is_string($prefix) || ! is_string($relSrc)) {
                continue;
            }
            $targetSrc = realpath($pkgDir . DIRECTORY_SEPARATOR . trim($relSrc, '/\\'));
            if ($targetSrc !== false && str_starts_with($targetSrc, $boundaryPrefix) && is_dir($targetSrc)) {
                $existingPaths = $prefixes[$prefix] ?? [];
                if (! in_array($targetSrc, $existingPaths, true)) {
                    $composerLoader->addPsr4($prefix, $targetSrc);
                }
            }
        }
    }

    $validManifestPaths[] = $realManifest;
    $manifestDataMap[$realManifest] = $data;
}

// 4. Resolve enabled package IDs
$enabledIds = OptionalPackageComposition::parseEnabledPackageIds(
    (string) env('LARASEED_OPTIONAL_PACKAGES', ''),
);

// 5. Partition manifests: ensure active packages are strictly validated while inactive unresolvable packages do not crash bootstrap
$activeOrResolvablePaths = [];

foreach ($validManifestPaths as $path) {
    $data = $manifestDataMap[$path];
    $id = $data['extra']['laraseed']['id'] ?? null;
    $provider = $data['extra']['laraseed']['provider'] ?? null;

    $isActive = is_string($id) && in_array($id, $enabledIds, true);

    if ($isActive) {
        // Active packages must always be passed to loader for strict validation & error reporting
        $activeOrResolvablePaths[] = $path;
    } else {
        // Inactive packages: include if provider class is resolvable
        if (is_string($provider) && class_exists($provider)) {
            $activeOrResolvablePaths[] = $path;
        }
    }
}

$catalog = (new OptionalPackageManifestLoader)->load($activeOrResolvablePaths);

$composition = new OptionalPackageComposition($catalog, $enabledIds);

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