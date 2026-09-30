<?php

use Webkul\Core\Packages\OptionalPackageComposition;
use Webkul\Core\Packages\OptionalPackageManifestLoader;

$catalog = (new OptionalPackageManifestLoader)->load([
    base_path('packages/Webkul/Student/composer.json'),
    base_path('packages/Webkul/LostAndFound/composer.json'),
    base_path('packages/Webkul/Website/composer.json'),
]);

$enabled = OptionalPackageComposition::parseEnabledPackageIds(
    (string) env('CAMPUSHUB_OPTIONAL_PACKAGES', ''),
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
