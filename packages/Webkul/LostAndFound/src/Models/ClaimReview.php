<?php

namespace Webkul\LostAndFound\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;
use Webkul\LostAndFound\Enums\ClaimStatus;
use Webkul\User\Models\UserProxy;

class ClaimReview extends Model
{
    protected $table = 'lost_found_claim_reviews';

    protected $fillable = [
        'claim_id',
        'reviewer_user_id',
        'from_status',
        'to_status',
        'claimant_message',
        'staff_notes',
        'reviewed_at',
    ];

    protected $hidden = [
        'claimant_message',
        'staff_notes',
    ];

    protected $casts = [
        'from_status' => ClaimStatus::class,
        'to_status' => ClaimStatus::class,
        'claimant_message' => 'encrypted',
        'staff_notes' => 'encrypted',
        'reviewed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Claim review history is append-only.');
        });

        static::deleting(static function (): never {
            throw new LogicException('Claim review history is append-only.');
        });
    }

    public function claim()
    {
        return $this->belongsTo(LostFoundClaimProxy::modelClass(), 'claim_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(UserProxy::modelClass(), 'reviewer_user_id');
    }
}
