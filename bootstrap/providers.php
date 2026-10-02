<?php

if (file_exists(base_path('app/Providers/AppServiceProvider.php'))) {
    require_once base_path('app/Providers/AppServiceProvider.php');
}

$optionalProviders = config('laraseed.optional_packages.providers', []);

return [
    \App\Providers\AppServiceProvider::class,
    \Laraseed\PackageGenerator\Providers\PackageGeneratorServiceProvider::class,
    ...$optionalProviders,
];