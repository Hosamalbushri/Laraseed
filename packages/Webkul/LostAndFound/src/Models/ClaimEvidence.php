<?php

namespace Webkul\LostAndFound\Models;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use LogicException;
use Webkul\LostAndFound\Enums\EvidenceType;

class ClaimEvidence extends Model
{
    protected $table = 'lost_found_claim_evidence';

    protected $fillable = [
        'claim_id',
        'evidence_type',
        'text_value',
        'file_path',
        'storage_key_hash',
        'original_name',
        'mime_type',
        'byte_size',
        'submitted_at',
    ];

    protected $hidden = [
        'text_value',
        'file_path',
        'storage_key_hash',
        'original_name',
        'mime_type',
        'byte_size',
    ];

    protected $casts = [
        'evidence_type' => EvidenceType::class,
        'text_value' => 'encrypted',
        'file_path' => 'encrypted',
        'original_name' => 'encrypted',
        'byte_size' => 'integer',
        'submitted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(static function (ClaimEvidence $evidence): void {
            $isImage = $evidence->evidence_type === EvidenceType::IMAGE_ATTACHMENT;
            $fileMetadata = [
                $evidence->file_path,
                $evidence->storage_key_hash,
                $evidence->mime_type,
                $evidence->byte_size,
            ];

            if ($isImage) {
                if ($evidence->text_value !== null
                    || in_array(null, $fileMetadata, true)
                    || ! in_array($evidence->mime_type, ['image/jpeg', 'image/png', 'image/webp'], true)
                    || ! is_string($evidence->storage_key_hash)
                    || preg_match('/\A[a-f0-9]{64}\z/', $evidence->storage_key_hash) !== 1
                    || (int) $evidence->byte_size < 1) {
                    throw new InvalidArgumentException('Image evidence requires one complete, valid private-file metadata set.');
                }

                return;
            }

            if (array_filter($fileMetadata, static fn ($value): bool => $value !== null) !== []) {
                throw new InvalidArgumentException('Non-file evidence cannot carry private-file metadata.');
            }
        });

        static::updating(static function (): never {
            throw new LogicException('Claim evidence is append-only.');
        });

        static::deleting(static function (): never {
            throw new LogicException('Claim evidence is append-only.');
        });
    }

    public function claim()
    {
        return $this->belongsTo(LostFoundClaimProxy::modelClass(), 'claim_id');
    }
}
