<?php

use Webkul\Admin\Providers\ModuleServiceProvider as AdminModuleServiceProvider;
use Webkul\Core\Providers\ModuleServiceProvider as CoreModuleServiceProvider;
use Webkul\DataGrid\Providers\ModuleServiceProvider as DataGridModuleServiceProvider;
use Webkul\Event\Providers\ModuleServiceProvider as EventModuleServiceProvider;
use Webkul\LostAndFound\Providers\ModuleServiceProvider as LostAndFoundModuleServiceProvider;
use Webkul\User\Providers\ModuleServiceProvider as UserModuleServiceProvider;

return [
    'modules' => [
        AdminModuleServiceProvider::class,
        CoreModuleServiceProvider::class,
        DataGridModuleServiceProvider::class,
        UserModuleServiceProvider::class,
        EventModuleServiceProvider::class,
        LostAndFoundModuleServiceProvider::class,
    ],

    'register_route_models' => true,
];
