<?php

return [
    'claim_evidence_images' => [
        'disk' => 'lost_found_private',

        /* Operational security defaults; these are configurable, not domain policy. */
        'max_bytes' => (int) env('LOST_FOUND_EVIDENCE_IMAGE_MAX_BYTES', 2 * 1024 * 1024),
        'max_width' => (int) env('LOST_FOUND_EVIDENCE_IMAGE_MAX_WIDTH', 4096),
        'max_height' => (int) env('LOST_FOUND_EVIDENCE_IMAGE_MAX_HEIGHT', 4096),
        'max_pixels' => (int) env('LOST_FOUND_EVIDENCE_IMAGE_MAX_PIXELS', 12_000_000),

        'jpeg_quality' => (int) env('LOST_FOUND_EVIDENCE_JPEG_QUALITY', 90),
        'png_compression' => (int) env('LOST_FOUND_EVIDENCE_PNG_COMPRESSION', 6),
        'webp_quality' => (int) env('LOST_FOUND_EVIDENCE_WEBP_QUALITY', 90),
    ],
];
