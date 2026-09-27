<?php

namespace Webkul\Shop\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [];
}
