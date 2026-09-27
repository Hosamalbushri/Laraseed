<?php

namespace Webkul\Event\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Event\Contracts\EventField;

class EventFieldRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model()
    {
        return EventField::class;
    }
}
