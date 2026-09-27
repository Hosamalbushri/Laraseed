<?php

namespace Webkul\LostAndFound\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Webkul\LostAndFound\Enums\FoundItemImageVisibility;

class UploadFoundItemImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxKilobytes = (int) ceil(config('lost_found.claim_evidence_images.max_bytes', 2048 * 1024) / 1024);

        return [
            'visibility' => ['required', new Enum(FoundItemImageVisibility::class)],
            'image' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp', "max:{$maxKilobytes}"],
        ];
    }
}
