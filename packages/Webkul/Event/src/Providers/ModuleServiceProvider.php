<?php

namespace Webkul\Event\Providers;

use Webkul\Core\Providers\BaseModuleServiceProvider;
use Webkul\Event\Models\Event;
use Webkul\Event\Models\EventCategory;
use Webkul\Event\Models\EventField;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    /**
     * The models to be used by this module.
     *
     * @var array
     */
    protected $models = [
        EventCategory::class,
        Event::class,
        EventField::class,
    ];
}
