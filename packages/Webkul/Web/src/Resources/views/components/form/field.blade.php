@props([
    'name' => null,
    'label' => null,
    'id' => null,
    'required' => false,
    'help' => null,
    'error' => null,
])

@php
    $fieldId = $id ?? ($name ? 'field-' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name) : 'web-field-' . bin2hex(random_bytes(4)));
    $helpId = $help ? $fieldId . '-help' : null;
    $errorId = $error ? $fieldId . '-error' : null;

    $describedBy = implode(' ', array_filter([$helpId, $errorId]));
@endphp

<div {{ $attributes->merge(['class' => 'web-field' . ($error ? ' has-error' : '')]) }}>
    @if ($label)
        <label for="{{ $fieldId }}" class="web-field__label">
            {{ $label }}
            @if ($required)
                <span class="web-field__required" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    <div class="web-field__control">
        {{ $slot }}
    </div>

    @if ($help)
        <p id="{{ $helpId }}" class="web-field__help">
            {{ $help }}
        </p>
    @endif

    @if ($error)
        <p id="{{ $errorId }}" class="web-field__error" role="alert">
            {{ $error }}
        </p>
    @endif
</div>
