<?php

use App\Providers\AppServiceProvider;
use Konekt\Concord\ConcordServiceProvider;
use Prettus\Repository\Providers\RepositoryServiceProvider;
use Webkul\Admin\Providers\AdminServiceProvider;
use Webkul\Core\Providers\CoreServiceProvider;
use Webkul\DataGrid\Providers\DataGridServiceProvider;
use Webkul\DebugBar\Providers\DebugBarServiceProvider;
use Webkul\Installer\Providers\InstallerServiceProvider;
use Webkul\User\Providers\UserServiceProvider;

$optionalProviders = config('laraseed.optional_packages.providers', []);

return [
    /*
     * Package Service Providers...
     */
    ConcordServiceProvider::class,
    RepositoryServiceProvider::class,

    /*
     * Application Service Providers...
     */
    AppServiceProvider::class,

    /*
     * Webkul Service Providers...
     */
    AdminServiceProvider::class,
    CoreServiceProvider::class,
    DataGridServiceProvider::class,
    InstallerServiceProvider::class,
    UserServiceProvider::class,
    DebugBarServiceProvider::class,
    ...$optionalProviders,
];
