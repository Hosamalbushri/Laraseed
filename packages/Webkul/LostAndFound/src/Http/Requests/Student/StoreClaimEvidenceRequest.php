<?php

namespace Webkul\LostAndFound\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\LostAndFound\Enums\EvidenceType;

class StoreClaimEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('student')->check();
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(array_map(
                fn (EvidenceType $type): string => $type->value,
                array_filter(EvidenceType::cases(), fn (EvidenceType $type): bool => $type !== EvidenceType::IMAGE_ATTACHMENT)
            ))],
            'content' => ['required', 'string', 'max:5000'],
        ];
    }

    public function validatedData(): array
    {
        return $this->only([
            'type',
            'content',
        ]);
    }
}
