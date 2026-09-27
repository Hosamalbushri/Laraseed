<?php

namespace Webkul\LostAndFound\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\LostAndFound\Contracts\FoundItemImage as FoundItemImageContract;
use Webkul\LostAndFound\Enums\FoundItemImageVisibility;
use Webkul\User\Models\UserProxy;

class FoundItemImage extends Model implements FoundItemImageContract
{
    protected $table = 'lost_found_item_images';

    protected $fillable = [
        'found_item_id',
        'created_by_user_id',
        'visibility',
        'storage_key',
        'mime_type',
        'byte_size',
        'sort_order',
    ];

    protected $hidden = [
        'storage_key',
    ];

    protected $casts = [
        'visibility' => FoundItemImageVisibility::class,
        'byte_size' => 'integer',
        'sort_order' => 'integer',
    ];

    public function foundItem(): BelongsTo
    {
        return $this->belongsTo(FoundItemProxy::modelClass(), 'found_item_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(UserProxy::modelClass(), 'created_by_user_id');
    }
}
