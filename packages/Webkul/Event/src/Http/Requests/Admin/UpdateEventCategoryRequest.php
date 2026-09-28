<?php

namespace Webkul\Event\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateEventCategoryRequest extends StoreEventCategoryRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'parent_id' => [
                'nullable',
                'integer',
                'exists:event_categories,id',
                Rule::notIn([(int) $this->route('id')]),
            ],
        ];
    }
}
