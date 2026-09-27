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
use Webkul\LostAndFound\Enums\FoundItemImageVisibility;
use Webkul\LostAndFound\Models\FoundItem;
use Webkul\LostAndFound\Models\FoundItemImage;
use Webkul\User\Models\User;

class FoundItemImageService
{
    public function addImage(
        FoundItem $item,
        User $creator,
        FoundItemImageVisibility $visibility,
        UploadedFile $image,
        int $sortOrder = 0,
    ): FoundItemImage {
        if (! $item->exists) {
            throw new InvalidArgumentException('FoundItem image persistence requires a saved item.');
        }

        if (! $creator->exists) {
            throw new InvalidArgumentException('FoundItem image persistence requires a valid creator.');
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

        $privateDiskName = (string) config('lost_found.claim_evidence_images.disk', 'lost_found_private');
        $privateDisk = Storage::disk($privateDiskName);

        $targetDiskName = match ($visibility) {
            FoundItemImageVisibility::PUBLIC_SAFE => 'public',
            FoundItemImageVisibility::STAFF_ONLY => $privateDiskName,
        };
        $targetDisk = Storage::disk($targetDiskName);

        $stagingKey = 'lost-found/staging/'.Str::uuid().'.tmp';
        $pathPrefix = match ($visibility) {
            FoundItemImageVisibility::PUBLIC_SAFE => 'lost-found/items/public/',
            FoundItemImageVisibility::STAFF_ONLY => 'lost-found/items/private/',
        };
        $finalKey = $this->uniqueFinalKey($targetDisk, $pathPrefix, $validated['extension']);
        $finalized = false;

        try {
            if (! $privateDisk->put($stagingKey, $sanitizedBytes)) {
                throw new RuntimeException('Unable to stage the image upload.');
            }

            $record = DB::transaction(function () use (
                $item,
                $creator,
                $visibility,
                $privateDisk,
                $targetDisk,
                $stagingKey,
                $finalKey,
                &$finalized,
                $validated,
                $sanitizedBytes,
                $sortOrder,
            ): FoundItemImage {
                $lockedItem = FoundItem::query()
                    ->lockForUpdate()
                    ->findOrFail($item->getKey());

                if ($lockedItem->status->isTerminal()) {
                    throw new DomainException("FoundItem images cannot be added in terminal status [{$lockedItem->status->value}].");
                }

                if ($lockedItem->claims()->exists()) {
                    throw new DomainException('FoundItem images cannot be mutated after a claim exists.');
                }

                if ($targetDisk === $privateDisk) {
                    if (! $targetDisk->move($stagingKey, $finalKey)) {
                        throw new RuntimeException('Unable to finalize private image file.');
                    }
                } else {
                    if (! $targetDisk->put($finalKey, $sanitizedBytes)) {
                        throw new RuntimeException('Unable to finalize public image file.');
                    }
                }

                $finalized = true;

                return FoundItemImage::create([
                    'found_item_id' => $lockedItem->id,
                    'created_by_user_id' => $creator->id,
                    'visibility' => $visibility,
                    'storage_key' => $finalKey,
                    'mime_type' => $validated['mime'],
                    'byte_size' => strlen($sanitizedBytes),
                    'sort_order' => max(0, $sortOrder),
                ]);
            });

            $this->cleanup($privateDisk, $stagingKey);

            return $record->refresh();
        } catch (Throwable $exception) {
            $this->cleanup($privateDisk, $stagingKey);

            if ($finalized || $this->exists($targetDisk, $finalKey)) {
                $this->cleanup($targetDisk, $finalKey);
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

    private function uniqueFinalKey($disk, string $prefix, string $extension): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $key = $prefix.Str::uuid().'/'.Str::uuid().'.'.$extension;

            if (! $disk->exists($key)) {
                return $key;
            }
        }

        throw new RuntimeException('Unable to allocate a unique storage key.');
    }

    private function exists($disk, string $key): bool
    {
        try {
            return $disk->exists($key);
        } catch (Throwable $exception) {
            Log::error('Unable to inspect an image file during compensation.', [
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
                throw new RuntimeException('Image cleanup returned false.');
            }
        } catch (Throwable $exception) {
            Log::error('Unable to clean up an image file.', [
                'storage_key' => $key,
                'exception' => $exception::class,
            ]);
        }
    }
}
