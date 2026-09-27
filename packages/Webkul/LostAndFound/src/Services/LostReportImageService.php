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
use Webkul\LostAndFound\Enums\ReportStatus;
use Webkul\LostAndFound\Models\LostReport;
use Webkul\LostAndFound\Models\LostReportImage;

class LostReportImageService
{
    private const ALLOWED_STATES = [
        ReportStatus::DRAFT,
        ReportStatus::ACTIVE,
    ];

    public function addImage(
        LostReport $report,
        UploadedFile $image,
        int $sortOrder = 0,
    ): LostReportImage {
        if (! $report->exists) {
            throw new InvalidArgumentException('LostReport image persistence requires a saved report.');
        }

        $limits = $this->limits();
        $sanitizer = new LostFoundRasterSanitizer;

        $validated = $sanitizer->sanitize(
            $image,
            $limits,
            [
                'jpeg_quality' => (int) config('lost_found.claim_evidence_images.jpeg_quality', 90),
                'png_compression' => (int) config('lost_found.claim_evidence_images.png_compression', 6),
                'webp_quality' => (int) config('lost_found.claim_evidence_images.webp_quality', 90),
            ]
        );

        $sanitizedBytes = $validated['sanitized_bytes'];

        $diskName = (string) config('lost_found.claim_evidence_images.disk', 'lost_found_private');
        $disk = Storage::disk($diskName);

        $stagingKey = 'lost-found/staging/'.Str::uuid().'.tmp';
        $finalKey = $this->uniqueFinalKey($disk, $validated['extension']);
        $finalized = false;

        try {
            if (! $disk->put($stagingKey, $sanitizedBytes)) {
                throw new RuntimeException('Unable to stage the private report image.');
            }

            $record = DB::transaction(function () use (
                $report,
                $disk,
                $stagingKey,
                $finalKey,
                &$finalized,
                $validated,
                $sanitizedBytes,
                $sortOrder,
            ): LostReportImage {
                $lockedReport = LostReport::query()
                    ->lockForUpdate()
                    ->findOrFail($report->getKey());

                if (! in_array($lockedReport->status, self::ALLOWED_STATES, true)) {
                    throw new DomainException("LostReport images cannot be added in current status [{$lockedReport->status->value}].");
                }

                if ($lockedReport->resolved_found_item_id !== null) {
                    throw new DomainException('LostReport images cannot be mutated after a confirmed resolution.');
                }

                if (! $disk->move($stagingKey, $finalKey)) {
                    throw new RuntimeException('Unable to finalize private report image file.');
                }

                $finalized = true;

                return LostReportImage::create([
                    'lost_report_id' => $lockedReport->id,
                    'storage_key' => $finalKey,
                    'mime_type' => $validated['mime'],
                    'byte_size' => strlen($sanitizedBytes),
                    'sort_order' => max(0, $sortOrder),
                ]);
            });

            $this->cleanup($disk, $stagingKey);

            return $record->refresh();
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
            'max_bytes' => (int) config('lost_found.claim_evidence_images.max_bytes', 2 * 1024 * 1024),
            'max_width' => (int) config('lost_found.claim_evidence_images.max_width', 4096),
            'max_height' => (int) config('lost_found.claim_evidence_images.max_height', 4096),
            'max_pixels' => (int) config('lost_found.claim_evidence_images.max_pixels', 12_000_000),
        ];

        if (min($limits) < 1) {
            throw new RuntimeException('Image limits must be positive integers.');
        }

        return $limits;
    }

    private function uniqueFinalKey($disk, string $extension): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $key = 'lost-found/reports/'.Str::uuid().'/'.Str::uuid().'.'.$extension;

            if (! $disk->exists($key)) {
                return $key;
            }
        }

        throw new RuntimeException('Unable to allocate a unique private report image storage key.');
    }

    private function exists($disk, string $key): bool
    {
        try {
            return $disk->exists($key);
        } catch (Throwable $exception) {
            Log::error('Unable to inspect a private report image file during compensation.', [
                'storage_key' => $key,
                'exception' => $exception::class,
            ]);

            return false;
        }
    }

    private function cleanup($disk, string $key): void
    {
        try {
            if ($disk->exists($key) && ! $disk->delete($key)) {
                throw new RuntimeException('Private report image cleanup returned false.');
            }
        } catch (Throwable $exception) {
            Log::error('Unable to clean up a private report image file.', [
                'storage_key' => $key,
                'exception' => $exception::class,
            ]);
        }
    }
}
