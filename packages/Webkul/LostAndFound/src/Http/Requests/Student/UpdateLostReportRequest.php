<?php

namespace Webkul\LostAndFound\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLostReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('student')->check();
    }

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'required', 'integer', 'exists:lost_found_categories,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'lost_at' => ['nullable', 'date'],
            'lost_location' => ['nullable', 'string', 'max:255'],
            'private_description' => ['nullable', 'string'],
        ];
    }

    public function validatedData(): array
    {
        return $this->only([
            'category_id',
            'title',
            'description',
            'lost_at',
            'lost_location',
            'private_description',
        ]);
    }
}
