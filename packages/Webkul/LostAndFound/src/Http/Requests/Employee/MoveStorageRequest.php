<?php

namespace Webkul\LostAndFound\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class MoveStorageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'to_storage_location' => ['required', 'string', 'min:1', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'expected_custodian_user_id' => ['nullable', 'integer'],
            'expected_storage_location' => ['nullable', 'string', 'max:255'],
        ];
    }
}
