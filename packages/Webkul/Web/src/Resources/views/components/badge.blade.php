@props([
    'variant' => 'neutral',
    'size' => 'md',
])

@php
    $validVariants = ['primary', 'secondary', 'success', 'warning', 'danger', 'neutral'];
    $normalizedVariant = in_array($variant, $validVariants, true) ? $variant : 'neutral';

    $validSizes = ['sm', 'md'];
    $normalizedSize = in_array($size, $validSizes, true) ? $size : 'md';

    $classes = 'web-badge web-badge--' . $normalizedVariant . ' web-badge--' . $normalizedSize;
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
