@props([
    'id' => null,
    'flush' => false,
    'alwaysOpen' => false,
])

@php
    $accordionId = $id ?? 'web-accordion-' . bin2hex(random_bytes(4));
    $classes = 'web-accordion' . ($flush ? ' web-accordion--flush' : '');
@endphp

<div
    id="{{ $accordionId }}"
    data-web-accordion
    data-web-accordion-always-open="{{ $alwaysOpen ? 'true' : 'false' }}"
    {{ $attributes->merge(['class' => $classes]) }}
>
    {{ $slot }}
</div>
