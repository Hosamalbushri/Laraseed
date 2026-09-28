<?php

namespace Webkul\LostAndFound\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class ReceiveCustodyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'storage_location' => ['required', 'string', 'min:1', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
