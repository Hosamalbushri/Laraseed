<?php

use Webkul\Core\Packages\OptionalPackageComposition;
use Webkul\Core\Packages\OptionalPackageManifestLoader;

$catalog = (new OptionalPackageManifestLoader)->load([]);

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
