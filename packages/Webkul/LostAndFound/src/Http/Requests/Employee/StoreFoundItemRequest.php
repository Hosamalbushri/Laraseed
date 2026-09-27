<?php

namespace Webkul\LostAndFound\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class StoreFoundItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:lost_found_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'found_at' => ['required', 'date'],
            'found_location' => ['required', 'string', 'max:255'],
        ];
    }
}
