<?php

namespace Webkul\LostAndFound\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;
use Webkul\LostAndFound\Enums\CustodyEventType;
use Webkul\User\Models\UserProxy;

class CustodyRecord extends Model
{
    protected $table = 'lost_found_custody_records';

    protected $fillable = [
        'found_item_id',
        'event_type',
        'actor_user_id',
        'from_custodian_user_id',
        'to_custodian_user_id',
        'from_storage_location',
        'to_storage_location',
        'notes',
        'occurred_at',
    ];

    protected $hidden = [
        'actor_user_id',
        'from_custodian_user_id',
        'to_custodian_user_id',
        'from_storage_location',
        'to_storage_location',
        'notes',
    ];

    protected $casts = [
        'event_type' => CustodyEventType::class,
        'from_storage_location' => 'encrypted',
        'to_storage_location' => 'encrypted',
        'notes' => 'encrypted',
        'occurred_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Custody history is append-only.');
        });

        static::deleting(static function (): never {
            throw new LogicException('Custody history is append-only.');
        });
    }

    public function foundItem()
    {
        return $this->belongsTo(FoundItemProxy::modelClass(), 'found_item_id');
    }

    public function actor()
    {
        return $this->belongsTo(UserProxy::modelClass(), 'actor_user_id');
    }

    public function fromCustodian()
    {
        return $this->belongsTo(UserProxy::modelClass(), 'from_custodian_user_id');
    }

    public function toCustodian()
    {
        return $this->belongsTo(UserProxy::modelClass(), 'to_custodian_user_id');
    }
}
