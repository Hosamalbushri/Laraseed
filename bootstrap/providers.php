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
use Webkul\Student\Providers\StudentServiceProvider;
use Webkul\Theme\Providers\ThemeServiceProvider;
use Webkul\User\Providers\UserServiceProvider;
use Webkul\Web\Providers\WebServiceProvider;

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
    EventServiceProvider::class,
    StudentServiceProvider::class,
    LostAndFoundServiceProvider::class,
    ThemeServiceProvider::class,
    WebServiceProvider::class,
];
