<?php

namespace Webkul\LostAndFound\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class ApproveClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
