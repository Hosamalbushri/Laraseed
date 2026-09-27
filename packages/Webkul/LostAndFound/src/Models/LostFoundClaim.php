<?php

namespace Webkul\LostAndFound\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\LostAndFound\Contracts\LostFoundClaim as LostFoundClaimContract;
use Webkul\LostAndFound\Enums\ClaimStatus;
use Webkul\Student\Models\Student;

class LostFoundClaim extends Model implements LostFoundClaimContract
{
    protected $table = 'lost_found_claims';

    protected $fillable = [
        'found_item_id',
        'claimant_student_id',
        'status',
        'submitted_at',
        'withdrawn_at',
    ];

    protected $casts = [
        'status' => ClaimStatus::class,
        'submitted_at' => 'datetime',
        'withdrawn_at' => 'datetime',
    ];

    public function foundItem()
    {
        return $this->belongsTo(FoundItemProxy::modelClass(), 'found_item_id');
    }

    public function claimant()
    {
        return $this->belongsTo(Student::class, 'claimant_student_id');
    }

    public function evidence()
    {
        return $this->hasMany(ClaimEvidence::class, 'claim_id');
    }

    public function reviews()
    {
        return $this->hasMany(ClaimReview::class, 'claim_id');
    }

    public function handover()
    {
        return $this->hasOne(Handover::class, 'claim_id');
    }
}
