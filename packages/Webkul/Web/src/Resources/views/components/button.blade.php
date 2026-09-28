@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'disabled' => false,
    'href' => null,
    'target' => null,
])

@php
    $validVariants = ['primary', 'secondary', 'outline', 'ghost', 'danger'];
    $normalizedVariant = in_array($variant, $validVariants, true) ? $variant : 'primary';

    $validSizes = ['sm', 'md', 'lg'];
    $normalizedSize = in_array($size, $validSizes, true) ? $size : 'md';

    $baseClasses = 'web-button web-button--' . $normalizedVariant . ' web-button--' . $normalizedSize;
@endphp

@if ($href)
    <a
        href="{{ $href }}"
        role="button"
        @if ($target) target="{{ $target }}" @endif
        @if ($disabled) aria-disabled="true" tabindex="-1" @endif
        {{ $attributes->merge(['class' => $baseClasses . ($disabled ? ' is-disabled' : '')]) }}
    >
        {{ $slot }}
    </a>
@else
    <button
        type="{{ in_array($type, ['button', 'submit', 'reset'], true) ? $type : 'button' }}"
        @if ($disabled) disabled @endif
        {{ $attributes->merge(['class' => $baseClasses]) }}
    >
        {{ $slot }}
    </button>
@endif
