<?php

namespace Webkul\LostAndFound\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;
use Webkul\Student\Models\Student;
use Webkul\User\Models\UserProxy;

class Handover extends Model
{
    protected $table = 'lost_found_handovers';

    protected $fillable = [
        'found_item_id',
        'claim_id',
        'recipient_student_id',
        'staff_user_id',
        'verification_method',
        'verification_note',
        'handed_over_at',
    ];

    protected $hidden = [
        'recipient_student_id',
        'staff_user_id',
        'verification_method',
        'verification_note',
    ];

    protected $casts = [
        'verification_method' => 'encrypted',
        'verification_note' => 'encrypted',
        'handed_over_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Handover history is immutable.');
        });

        static::deleting(static function (): never {
            throw new LogicException('Handover history is immutable.');
        });
    }

    public function foundItem()
    {
        return $this->belongsTo(FoundItemProxy::modelClass(), 'found_item_id');
    }

    public function claim()
    {
        return $this->belongsTo(LostFoundClaimProxy::modelClass(), 'claim_id');
    }

    public function recipient()
    {
        return $this->belongsTo(Student::class, 'recipient_student_id');
    }

    public function staff()
    {
        return $this->belongsTo(UserProxy::modelClass(), 'staff_user_id');
    }
}
