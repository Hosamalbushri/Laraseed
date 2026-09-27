<?php

namespace Webkul\LostAndFound\Models;

use Illuminate\Database\Eloquent\Model;

class FoundItemPrivateDetail extends Model
{
    protected $table = 'lost_found_item_private_details';

    protected $fillable = [
        'identifying_details',
        'serial_fragment',
        'staff_notes',
    ];

    protected $hidden = [
        'identifying_details',
        'serial_fragment',
        'staff_notes',
    ];

    protected $casts = [
        'identifying_details' => 'encrypted',
        'serial_fragment' => 'encrypted',
        'staff_notes' => 'encrypted',
    ];

    public function foundItem()
    {
        return $this->belongsTo(FoundItemProxy::modelClass(), 'found_item_id');
    }
}
