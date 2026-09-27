<?php

namespace Webkul\LostAndFound\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\LostAndFound\Contracts\FoundItem as FoundItemContract;
use Webkul\LostAndFound\Enums\FoundItemImageVisibility;
use Webkul\LostAndFound\Enums\ItemStatus;
use Webkul\LostAndFound\Services\PublicReference;
use Webkul\User\Models\UserProxy;

class FoundItem extends Model implements FoundItemContract
{
    protected $table = 'lost_found_items';

    protected $fillable = [
        'public_reference',
        'category_id',
        'logged_by_user_id',
        'status',
        'title',
        'public_description',
        'found_location',
        'found_at',
        'reported_at',
    ];

    protected $hidden = [
        'public_reference_key',
        'approved_claim_id',
        'current_custodian_user_id',
        'current_storage_location',
        'custody_started_at',
        'custody_changed_at',
    ];

    protected $casts = [
        'status' => ItemStatus::class,
        'found_at' => 'datetime',
        'reported_at' => 'datetime',
        'custody_started_at' => 'datetime',
        'custody_changed_at' => 'datetime',
    ];

    public function setPublicReferenceAttribute(string $value): void
    {
        $reference = PublicReference::fromString($value);

        $this->attributes['public_reference'] = $reference->getValue();
        $this->attributes['public_reference_key'] = PublicReference::normalize($value);
    }

    public function category()
    {
        return $this->belongsTo(LostFoundCategoryProxy::modelClass(), 'category_id');
    }

    public function loggedBy()
    {
        return $this->belongsTo(UserProxy::modelClass(), 'logged_by_user_id');
    }

    public function privateDetail()
    {
        return $this->hasOne(FoundItemPrivateDetail::class, 'found_item_id');
    }

    public function resolvedLostReports()
    {
        return $this->hasMany(LostReportProxy::modelClass(), 'resolved_found_item_id');
    }

    public function claims()
    {
        return $this->hasMany(LostFoundClaimProxy::modelClass(), 'found_item_id');
    }

    public function approvedClaim()
    {
        return $this->belongsTo(LostFoundClaimProxy::modelClass(), 'approved_claim_id');
    }

    public function currentCustodian()
    {
        return $this->belongsTo(UserProxy::modelClass(), 'current_custodian_user_id');
    }

    public function custodyRecords()
    {
        return $this->hasMany(CustodyRecord::class, 'found_item_id')
            ->orderBy('occurred_at')
            ->orderBy('id');
    }

    public function handover()
    {
        return $this->hasOne(Handover::class, 'found_item_id');
    }

    public function images()
    {
        return $this->hasMany(FoundItemImageProxy::modelClass(), 'found_item_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function coverImage()
    {
        return $this->hasOne(FoundItemImageProxy::modelClass(), 'found_item_id')
            ->where('visibility', FoundItemImageVisibility::PUBLIC_SAFE->value)
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
