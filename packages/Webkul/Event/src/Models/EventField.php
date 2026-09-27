<?php

namespace Webkul\Event\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Event\Contracts\EventField as EventFieldContract;

class EventField extends Model implements EventFieldContract
{
    protected $table = 'event_fields';

    protected $fillable = [
        'name',
        'type',
        'value',
        'event_id',
    ];

    public function event()
    {
        return $this->belongsTo(EventProxy::modelClass());
    }
}
