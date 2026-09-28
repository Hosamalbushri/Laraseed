<?php

namespace Webkul\Event\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentEventSubscriptionRequest extends FormRequest
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
            'event_id' => ['required', 'integer', 'exists:events,id'],
        ];
    }
}
