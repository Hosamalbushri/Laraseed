<?php

namespace Laraseed\Contacts\Providers;

use Konekt\Concord\BaseModuleServiceProvider;
use Laraseed\Contacts\Models\Contact;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    /**
     * Models registered by this Concord module.
     *
     * @var array<int, string>
     */
    protected $models = [
        Contact::class,
    ];
}
