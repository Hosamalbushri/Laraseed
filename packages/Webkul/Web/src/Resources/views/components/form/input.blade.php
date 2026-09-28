@props([
    'name' => null,
    'id' => null,
    'type' => 'text',
    'value' => null,
    'disabled' => false,
    'readonly' => false,
    'required' => false,
    'placeholder' => null,
    'autocomplete' => null,
    'invalid' => false,
    'describedBy' => null,
])

@php
    $validTypes = ['text', 'email', 'password', 'number', 'tel', 'url', 'search', 'date', 'time', 'datetime-local', 'month', 'week', 'color', 'hidden'];
    $normalizedType = in_array(strtolower($type), $validTypes, true) ? strtolower($type) : 'text';

    $inputId = $id ?? ($name ? 'field-' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name) : null);
    $classes = 'web-input' . ($invalid ? ' is-invalid' : '');
@endphp

<input
    type="{{ $normalizedType }}"
    @if ($name) name="{{ $name }}" @endif
    @if ($inputId) id="{{ $inputId }}" @endif
    @if ($value !== null) value="{{ $value }}" @endif
    @if ($placeholder) placeholder="{{ $placeholder }}" @endif
    @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
    @if ($required) required @endif
    @if ($disabled) disabled @endif
    @if ($readonly) readonly @endif
    aria-invalid="{{ $invalid ? 'true' : 'false' }}"
    @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
    {{ $attributes->merge(['class' => $classes]) }}
/>
