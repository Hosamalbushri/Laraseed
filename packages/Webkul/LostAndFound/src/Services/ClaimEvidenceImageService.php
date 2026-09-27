<?php

namespace Webkul\LostAndFound\Services;

use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use Webkul\LostAndFound\Enums\ClaimStatus;
use Webkul\LostAndFound\Enums\EvidenceType;
use Webkul\LostAndFound\Models\ClaimEvidence;
use Webkul\LostAndFound\Models\LostFoundClaim;

class ClaimEvidenceImageService
{
    private const ALLOWED_STATES = [
        ClaimStatus::SUBMITTED,
        ClaimStatus::NEEDS_INFORMATION,
    ];

    public function store(LostFoundClaim $claim, UploadedFile $image): ClaimEvidence
    {
        if (! $claim->exists) {
            throw new InvalidArgumentException('Image evidence requires a persisted claim.');
        }

        $limits = $this->limits();
        $sanitizer = new LostFoundRasterSanitizer;

        try {
            $validated = $sanitizer->sanitize(
                $image,
                $limits,
                [
                    'jpeg_quality' => (int) config('lost_found.claim_evidence_images.jpeg_quality'),
                    'png_compression' => (int) config('lost_found.claim_evidence_images.png_compression'),
                    'webp_quality' => (int) config('lost_found.claim_evidence_images.webp_quality'),
                ]
            );
        } catch (InvalidArgumentException $e) {
            $message = match ($e->getMessage()) {
                'The file is not an approved JPEG, PNG, or WebP image.' => 'The evidence file is not an approved JPEG, PNG, or WebP image.',
                'The image exceeds the configured byte limit.', 'The normalized image exceeds the configured byte limit.' => 'The evidence image exceeds the configured byte limit.',
                'The image could not be decoded.' => 'The evidence image could not be decoded.',
                'Only JPEG, PNG, and WebP images are supported.' => 'Only JPEG, PNG, and WebP evidence images are supported.',
                'The image extension, MIME type, and decoded format must agree.' => 'The evidence image extension, MIME type, and decoded format must agree.',
                'The image exceeds the configured pixel limits.' => 'The evidence image exceeds the configured pixel limits.',
                'The image upload is invalid.' => 'The evidence image upload is invalid.',
                default => $e->getMessage(),
            };

            throw new InvalidArgumentException($message, 0, $e);
        }

        $sanitizedBytes = $validated['sanitized_bytes'];
        $originalName = $this->sanitizeOriginalName($image->getClientOriginalName());

        $diskName = (string) config('lost_found.claim_evidence_images.disk');

        if ($diskName === '') {
            throw new RuntimeException('The private claim-evidence disk is not configured.');
        }

        $disk = Storage::disk($diskName);
        $stagingKey = 'lost-found/staging/'.Str::uuid().'.tmp';
        $finalKey = $this->uniqueFinalKey($disk, $validated['extension']);
        $finalized = false;

        try {
            if (! $disk->put($stagingKey, $sanitizedBytes)) {
                throw new RuntimeException('Unable to stage the private evidence image.');
            }

            $evidence = DB::transaction(function () use (
                $claim,
                $disk,
                $stagingKey,
                $finalKey,
                &$finalized,
                $validated,
                $originalName,
                $sanitizedBytes,
            ): ClaimEvidence {
                $lockedClaim = LostFoundClaim::query()
                    ->lockForUpdate()
                    ->findOrFail($claim->getKey());

                if (! in_array($lockedClaim->status, self::ALLOWED_STATES, true)) {
                    throw new DomainException('Private image evidence cannot be added in the current claim state.');
                }

                if ($lockedClaim->handover()->exists()) {
                    throw new DomainException('Private image evidence cannot be added after physical handover.');
                }

                if (! $disk->move($stagingKey, $finalKey)) {
                    throw new RuntimeException('Unable to finalize the private evidence image.');
                }

                $finalized = true;

                return $lockedClaim->evidence()->create([
                    'evidence_type' => EvidenceType::IMAGE_ATTACHMENT,
                    'text_value' => null,
                    'file_path' => $finalKey,
                    'storage_key_hash' => hash('sha256', $finalKey),
                    'original_name' => $originalName,
                    'mime_type' => $validated['mime'],
                    'byte_size' => strlen($sanitizedBytes),
                    'submitted_at' => now(),
                ]);
            });

            $this->cleanup($disk, $stagingKey);

            return $evidence->refresh();
        } catch (Throwable $exception) {
            $this->cleanup($disk, $stagingKey);

            if ($finalized || $this->exists($disk, $finalKey)) {
                $this->cleanup($disk, $finalKey);
            }

            throw $exception;
        }
    }

    /** @return array{max_bytes: int, max_width: int, max_height: int, max_pixels: int} */
    private function limits(): array
    {
        $limits = [
            'max_bytes' => (int) config('lost_found.claim_evidence_images.max_bytes'),
            'max_width' => (int) config('lost_found.claim_evidence_images.max_width'),
            'max_height' => (int) config('lost_found.claim_evidence_images.max_height'),
            'max_pixels' => (int) config('lost_found.claim_evidence_images.max_pixels'),
        ];

        if (min($limits) < 1) {
            throw new RuntimeException('Private evidence image limits must be positive integers.');
        }

        return $limits;
    }

    private function sanitizeOriginalName(string $name): ?string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';
        $name = trim(mb_substr($name, 0, 255));

        if ($name === '') {
            return null;
        }

        SecurityInvariants::assertNoAuthSecrets($name);

        return $name;
    }

    private function uniqueFinalKey($disk, string $extension): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $key = 'lost-found/claims/'.Str::uuid().'/'.Str::uuid().'.'.$extension;

            if (! $disk->exists($key)) {
                return $key;
            }
        }

        throw new RuntimeException('Unable to allocate a unique private evidence storage key.');
    }

    private function exists($disk, string $key): bool
    {
        try {
            return $disk->exists($key);
        } catch (Throwable $exception) {
            Log::error('Unable to inspect a private evidence file during compensation.', [
                'storage_key_hash' => hash('sha256', $key),
                'exception' => $exception::class,
            ]);

            return false;
        }
    }

    private function cleanup($disk, string $key): void
    {
        try {
            if ($disk->exists($key) && ! $disk->delete($key)) {
                throw new RuntimeException('Private evidence cleanup returned false.');
            }
        } catch (Throwable $exception) {
            Log::error('Unable to clean up a private evidence file.', [
                'storage_key_hash' => hash('sha256', $key),
                'exception' => $exception::class,
            ]);
        }
    }
}
