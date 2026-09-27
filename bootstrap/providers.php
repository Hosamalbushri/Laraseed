<?php

use App\Providers\AppServiceProvider;
use Konekt\Concord\ConcordServiceProvider;
use Prettus\Repository\Providers\RepositoryServiceProvider;
use Webkul\Admin\Providers\AdminServiceProvider;
use Webkul\Core\Providers\CoreServiceProvider;
use Webkul\DataGrid\Providers\DataGridServiceProvider;
use Webkul\Event\Providers\EventServiceProvider;
use Webkul\Installer\Providers\InstallerServiceProvider;
use Webkul\LostAndFound\Providers\LostAndFoundServiceProvider;
use Webkul\Shop\Providers\ShopServiceProvider;
use Webkul\Student\Providers\StudentServiceProvider;
use Webkul\User\Providers\UserServiceProvider;

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
    ShopServiceProvider::class,
    AdminServiceProvider::class,
    CoreServiceProvider::class,
    DataGridServiceProvider::class,
    InstallerServiceProvider::class,
    UserServiceProvider::class,
    EventServiceProvider::class,
    StudentServiceProvider::class,
    LostAndFoundServiceProvider::class,
];
