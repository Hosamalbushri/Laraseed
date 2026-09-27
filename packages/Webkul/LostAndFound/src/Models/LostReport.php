<?php

namespace Webkul\LostAndFound\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\LostAndFound\Contracts\LostReport as LostReportContract;
use Webkul\LostAndFound\Enums\ReportStatus;
use Webkul\LostAndFound\Services\PublicReference;
use Webkul\Student\Models\Student;

class LostReport extends Model implements LostReportContract
{
    protected $table = 'lost_found_reports';

    protected $fillable = [
        'public_reference',
        'student_id',
        'category_id',
        'resolved_found_item_id',
        'status',
        'title',
        'public_description',
        'private_description',
        'lost_location',
        'lost_at',
        'submitted_at',
        'closed_at',
    ];

    protected $hidden = [
        'public_reference_key',
        'private_description',
    ];

    protected $casts = [
        'status' => ReportStatus::class,
        'private_description' => 'encrypted',
        'lost_at' => 'datetime',
        'submitted_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function setPublicReferenceAttribute(string $value): void
    {
        $reference = PublicReference::fromString($value);

        $this->attributes['public_reference'] = $reference->getValue();
        $this->attributes['public_reference_key'] = PublicReference::normalize($value);
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function category()
    {
        return $this->belongsTo(LostFoundCategoryProxy::modelClass(), 'category_id');
    }

    public function resolvedFoundItem()
    {
        return $this->belongsTo(FoundItemProxy::modelClass(), 'resolved_found_item_id');
    }

    public function images()
    {
        return $this->hasMany(LostReportImage::class, 'lost_report_id')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc');
    }
}
