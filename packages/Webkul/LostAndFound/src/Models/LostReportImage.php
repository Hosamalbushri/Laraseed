<?php

namespace Webkul\LostAndFound\Services;

namespace Webkul\LostAndFound\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class LostReportImage extends Model
{
    protected $table = 'lost_found_report_images';

    protected $fillable = [
        'lost_report_id',
        'storage_key',
        'mime_type',
        'byte_size',
        'sort_order',
    ];

    protected $hidden = [
        'storage_key',
    ];

    protected $casts = [
        'byte_size' => 'integer',
        'sort_order' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::updating(function () {
            throw new LogicException('LostReportImage metadata is append-only and cannot be updated.');
        });

        static::deleting(function () {
            throw new LogicException('LostReportImage metadata is append-only and cannot be deleted.');
        });
    }

    public function report()
    {
        return $this->belongsTo(LostReport::class, 'lost_report_id');
    }
}
