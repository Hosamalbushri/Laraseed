<?php

namespace Webkul\Event\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Event\Contracts\EventCategory;

class EventCategoryRepository extends Repository
{
    /**
     * Specify model class name.
     *
     * @return mixed
     */
    public function model()
    {
        return EventCategory::class;
    }
}
