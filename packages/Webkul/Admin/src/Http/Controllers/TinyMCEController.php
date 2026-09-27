<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TinyMCEController extends Controller
{
    /**
     * Storage folder path.
     */
    private string $storagePath = 'tinymce';

    /**
     * Upload file from tinymce.
     */
    public function upload(): JsonResponse
    {
        $media = $this->storeMedia();

        if (! empty($media)) {
            return response()->json([
                'location' => $media['file_url'],
            ]);
        }

        return response()->json([]);
    }

    /**
     * Store media.
     */
    public function storeMedia(): array
    {
        $validated = request()->validate([
            'file' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,gif,webp',
                'mimetypes:image/jpeg,image/png,image/gif,image/webp',
                'extensions:jpg,jpeg,png,gif,webp',
                'max:5120',
            ],
        ]);

        $file = $validated['file'];

        if (! $file instanceof UploadedFile) {
            return [];
        }

        $extension = match ($file->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        };

        $filename = Str::uuid().'.'.$extension;

        $path = $file->storeAs($this->storagePath, $filename, 'public');

        return [
            'file' => $path,
            'file_name' => $filename,
            'file_url' => Storage::disk('public')->url($path),
        ];
    }
}
