<?php

namespace Webkul\Event\Models;

use Illuminate\Database\Eloquent\Model;

class EventImage extends Model
{
    protected $table = 'event_images';

    protected $fillable = [
        'event_id',
        'path',
        'position',
    ];
}
