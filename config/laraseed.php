<?php

use Webkul\Core\Packages\OptionalPackageComposition;
use Webkul\Core\Packages\OptionalPackageManifestLoader;

$manifestPaths = array_values(array_filter(
    glob(dirname(__DIR__) . '/packages/*/*/composer.json') ?: [],
    function (string $path): bool {
        if (! is_file($path)) {
            return false;
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) && ($data['extra']['laraseed']['type'] ?? null) === 'optional';
    }
));

$catalog = (new OptionalPackageManifestLoader)->load($manifestPaths);

$enabled = OptionalPackageComposition::parseEnabledPackageIds(
    (string) env('LARASEED_OPTIONAL_PACKAGES', ''),
);

$composition = new OptionalPackageComposition($catalog, $enabled);

return [
    'optional_packages' => [
        'enabled' => $composition->enabledPackages(),
        'catalog' => $composition->packages(),
        'providers' => $composition->providers(),
        'concord_modules' => $composition->concordModules(),
        'dependencies' => $composition->dependencyGraph(),
    ],
];
