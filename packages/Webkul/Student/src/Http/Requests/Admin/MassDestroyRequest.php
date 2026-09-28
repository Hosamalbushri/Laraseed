<?php

namespace Webkul\Student\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MassDestroyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return bouncer()->hasPermission('students.delete');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'indices' => 'required|array',
            'indices.*' => 'integer|exists:students,id',
        ];
    }
}
