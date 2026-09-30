<?php

use Webkul\Admin\Providers\ModuleServiceProvider as AdminModuleServiceProvider;
use Webkul\Core\Providers\ModuleServiceProvider as CoreModuleServiceProvider;
use Webkul\DataGrid\Providers\ModuleServiceProvider as DataGridModuleServiceProvider;
use Webkul\User\Providers\ModuleServiceProvider as UserModuleServiceProvider;

$optionalModules = config('campushub.optional_packages.concord_modules', []);

return [
    'modules' => [
        AdminModuleServiceProvider::class,
        CoreModuleServiceProvider::class,
        DataGridModuleServiceProvider::class,
        UserModuleServiceProvider::class,
        ...$optionalModules,
    ],

    'register_route_models' => true,
];
