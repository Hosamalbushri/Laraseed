<?php

namespace Webkul\LostAndFound\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class UploadClaimEvidenceImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('student')->check();
    }

    public function rules(): array
    {
        $maxBytes = (int) config('lost_found.claim_evidence_images.max_bytes', 2 * 1024 * 1024);
        $maxKilobytes = (int) ceil($maxBytes / 1024);

        return [
            'image' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', "max:{$maxKilobytes}"],
        ];
    }
}
