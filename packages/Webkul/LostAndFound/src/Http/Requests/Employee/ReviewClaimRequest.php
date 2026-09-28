<?php

namespace Webkul\LostAndFound\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class ReviewClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:under_review,needs_information'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
