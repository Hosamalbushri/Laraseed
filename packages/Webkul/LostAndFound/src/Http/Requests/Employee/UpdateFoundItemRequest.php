<?php

namespace Webkul\LostAndFound\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFoundItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'integer', 'exists:lost_found_categories,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'found_at' => ['sometimes', 'date'],
            'found_location' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
