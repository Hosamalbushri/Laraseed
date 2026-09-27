<?php

namespace Webkul\Event\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Webkul\Event\Contracts\Event as EventContract;
use Webkul\Student\Models\Student;

class Event extends Model implements EventContract
{
    protected $table = 'events';

    protected $fillable = [
        'title',
        'event_date',
        'event_end_date',
        'organizer',
        'available_seats',
        'availability_use_seats',
        'availability_use_end_date',
        'image',
        'description',
        'status',
    ];

    protected $casts = [
        'event_date' => 'date',
        'event_end_date' => 'date',
        'availability_use_seats' => 'boolean',
        'availability_use_end_date' => 'boolean',
    ];

    public function categories()
    {
        return $this->belongsToMany(EventCategoryProxy::modelClass(), 'event_event_category', 'event_id', 'event_category_id')
            ->withTimestamps();
    }

    public function fields()
    {
        return $this->hasMany(EventFieldProxy::modelClass());
    }

    public function images()
    {
        return $this->hasMany(EventImage::class)->orderBy('position')->orderBy('id');
    }

    /**
     * Students registered for this event (portal subscriptions).
     */
    public function subscribers()
    {
        $studentModel = config('auth.providers.students.model', Student::class);

        return $this->belongsToMany($studentModel, 'event_student', 'event_id', 'student_id')
            ->withTimestamps();
    }

    /**
     * Whether the event passes configured availability rules (seats / end date), ignoring published flag.
     */
    public function isCurrentlyAvailable(): bool
    {
        if ($this->availability_use_seats) {
            if ($this->available_seats !== null && (int) $this->available_seats <= 0) {
                return false;
            }
        }

        if ($this->availability_use_end_date) {
            if (! $this->event_end_date) {
                return false;
            }

            $end = Carbon::parse($this->event_end_date)->startOfDay();
            if (Carbon::today()->gt($end)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Published and currently available for the public shop listing.
     */
    public function scopePublished($query)
    {
        return $query->where('status', true)
            ->where(function ($q) {
                $q->where('availability_use_seats', false)
                    ->orWhereNull('available_seats')
                    ->orWhere('available_seats', '>', 0);
            })
            ->where(function ($q) {
                $q->where('availability_use_end_date', false)
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('event_end_date')
                            ->whereDate('event_end_date', '>=', now()->toDateString());
                    });
            });
    }
}
