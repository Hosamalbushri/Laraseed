<?php

namespace Webkul\Event\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SaveEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'event_date' => ['required', 'date'],
            'event_end_date' => ['required', 'date', 'after_or_equal:event_date'],
            'organizer' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string'],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'exists:event_categories,id'],
            'available_seats' => ['nullable', 'integer', 'min:0'],
            'availability_use_seats' => ['nullable', 'boolean'],
            'status' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array'],
            'images.*' => ['nullable'],
            'description' => ['nullable', 'string'],
            'event_custom_fields_form' => ['nullable', 'boolean'],
            'fields' => ['nullable', 'array'],
            'fields.*.name' => ['required_with:fields', 'string'],
            'fields.*.type' => ['required_with:fields', 'string'],
            'fields.*.value' => ['nullable'],
            'fields.*.old_value' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->boolean('status'),
            'availability_use_seats' => $this->boolean('availability_use_seats'),
            'available_seats' => $this->input('available_seats') === ''
                ? null
                : $this->input('available_seats'),
        ]);
    }
}
