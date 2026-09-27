<?php

namespace Webkul\LostAndFound\Services;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use RuntimeException;

class LostFoundRasterSanitizer
{
    private const FORMATS = [
        IMAGETYPE_JPEG => ['mime' => 'image/jpeg', 'extension' => 'jpg', 'client_extensions' => ['jpg', 'jpeg']],
        IMAGETYPE_PNG => ['mime' => 'image/png', 'extension' => 'png', 'client_extensions' => ['png']],
        IMAGETYPE_WEBP => ['mime' => 'image/webp', 'extension' => 'webp', 'client_extensions' => ['webp']],
    ];

    /**
     * Sanitize and re-encode an uploaded raster image.
     *
     * @param  array{max_bytes: int, max_width: int, max_height: int, max_pixels: int}  $limits
     * @param  array{jpeg_quality?: int, png_compression?: int, webp_quality?: int}  $config
     * @return array{sanitized_bytes: string, mime: string, extension: string, type: int, width: int, height: int}
     */
    public function sanitize(UploadedFile $file, array $limits, array $config = []): array
    {
        if (! $file->isValid()) {
            throw new InvalidArgumentException('The image upload is invalid.');
        }

        $validator = Validator::make(
            ['image' => $file],
            ['image' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'extensions:jpg,jpeg,png,webp',
                'max:'.(int) ceil($limits['max_bytes'] / 1024),
            ]],
        );

        if ($validator->fails()) {
            throw new InvalidArgumentException('The file is not an approved JPEG, PNG, or WebP image.');
        }

        $size = $file->getSize();

        if (! is_int($size) || $size < 1 || $size > $limits['max_bytes']) {
            throw new InvalidArgumentException('The image exceeds the configured byte limit.');
        }

        $path = $file->getPathname();
        $dimensions = @getimagesize($path);

        if (! is_array($dimensions) || ! isset($dimensions[0], $dimensions[1], $dimensions[2])) {
            throw new InvalidArgumentException('The image could not be decoded.');
        }

        $width = (int) $dimensions[0];
        $height = (int) $dimensions[1];
        $type = (int) $dimensions[2];
        $format = self::FORMATS[$type] ?? null;

        if (! $format) {
            throw new InvalidArgumentException('Only JPEG, PNG, and WebP images are supported.');
        }

        $detectedMime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        $clientExtension = strtolower($file->getClientOriginalExtension());

        if ($detectedMime !== $format['mime']
            || ! in_array($clientExtension, $format['client_extensions'], true)) {
            throw new InvalidArgumentException('The image extension, MIME type, and decoded format must agree.');
        }

        if ($width < 1
            || $height < 1
            || $width > $limits['max_width']
            || $height > $limits['max_height']
            || ($width * $height) > $limits['max_pixels']) {
            throw new InvalidArgumentException('The image exceeds the configured pixel limits.');
        }

        $decoded = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
        };

        if (! $decoded instanceof GdImage) {
            throw new InvalidArgumentException('The image could not be decoded safely.');
        }

        try {
            if ($type === IMAGETYPE_JPEG) {
                $decoded = $this->normalizeJpegOrientation($decoded, $path);
            } else {
                imagealphablending($decoded, false);
                imagesavealpha($decoded, true);
            }

            $sanitizedBytes = $this->encode($decoded, $type, $config);
        } finally {
            imagedestroy($decoded);
        }

        if (strlen($sanitizedBytes) > $limits['max_bytes']) {
            throw new InvalidArgumentException('The normalized image exceeds the configured byte limit.');
        }

        return [
            'sanitized_bytes' => $sanitizedBytes,
            'mime' => $format['mime'],
            'extension' => $format['extension'],
            'type' => $type,
            'width' => imagesx($decoded),
            'height' => imagesy($decoded),
        ];
    }

    private function normalizeJpegOrientation(GdImage $image, string $path): GdImage
    {
        $metadata = function_exists('exif_read_data') ? @exif_read_data($path) : false;
        $orientation = is_array($metadata) ? (int) ($metadata['Orientation'] ?? 1) : 1;

        return match ($orientation) {
            2 => $this->flip($image, IMG_FLIP_HORIZONTAL),
            3 => $this->rotate($image, 180),
            4 => $this->flip($image, IMG_FLIP_VERTICAL),
            5 => $this->flip($this->rotate($image, -90), IMG_FLIP_HORIZONTAL),
            6 => $this->rotate($image, -90),
            7 => $this->flip($this->rotate($image, 90), IMG_FLIP_HORIZONTAL),
            8 => $this->rotate($image, 90),
            default => $image,
        };
    }

    private function flip(GdImage $image, int $mode): GdImage
    {
        if (! imageflip($image, $mode)) {
            imagedestroy($image);

            throw new RuntimeException('Unable to normalize image orientation.');
        }

        return $image;
    }

    private function rotate(GdImage $image, int $degrees): GdImage
    {
        $rotated = imagerotate($image, $degrees, 0);

        if (! $rotated instanceof GdImage) {
            imagedestroy($image);

            throw new RuntimeException('Unable to normalize image orientation.');
        }

        imagedestroy($image);

        return $rotated;
    }

    private function encode(GdImage $image, int $type, array $config): string
    {
        ob_start();

        try {
            $encoded = match ($type) {
                IMAGETYPE_JPEG => imagejpeg(
                    $image,
                    null,
                    (int) ($config['jpeg_quality'] ?? 90),
                ),
                IMAGETYPE_PNG => imagepng(
                    $image,
                    null,
                    (int) ($config['png_compression'] ?? 6),
                ),
                IMAGETYPE_WEBP => imagewebp(
                    $image,
                    null,
                    (int) ($config['webp_quality'] ?? 90),
                ),
            };

            $bytes = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        if (! $encoded || ! is_string($bytes) || $bytes === '') {
            throw new RuntimeException('Unable to normalize the image.');
        }

        return $bytes;
    }
}
