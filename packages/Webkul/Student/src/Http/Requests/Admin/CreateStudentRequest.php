<?php

namespace Webkul\Student\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return bouncer()->hasPermission('students.create');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'university_card_number' => 'required|string|max:255|unique:students,university_card_number',
            'registration_number' => 'nullable|string|max:255',
            'major' => 'nullable|string|max:255',
            'academic_level' => 'nullable|string|max:255',
            'password' => 'required|min:6|confirmed',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }
}
