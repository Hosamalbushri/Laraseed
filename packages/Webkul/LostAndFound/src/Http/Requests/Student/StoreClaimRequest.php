<?php

namespace Webkul\LostAndFound\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class StoreClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('student')->check();
    }

    public function rules(): array
    {
        return [
            'found_item_id' => ['required', 'integer', 'exists:lost_found_items,id'],
            'statement' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function validatedData(): array
    {
        return $this->only([
            'found_item_id',
            'statement',
        ]);
    }
}
