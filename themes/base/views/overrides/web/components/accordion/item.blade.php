@props([
    'id' => null,
    'title' => '',
    'expanded' => false,
])

@php
    $itemId = $id ?? 'web-acc-item-' . bin2hex(random_bytes(4));
    $triggerId = $itemId . '-trigger';
    $panelId = $itemId . '-panel';
@endphp

<div {{ $attributes->merge(['class' => 'web-accordion__item']) }}>
    <h3 class="web-accordion__header">
        <button
            type="button"
            id="{{ $triggerId }}"
            class="web-accordion__trigger"
            aria-expanded="{{ $expanded ? 'true' : 'false' }}"
            aria-controls="{{ $panelId }}"
            data-web-accordion-trigger
        >
            <span class="web-accordion__title">@if (isset($header)){{ $header }}@else{{ $title }}@endif</span>
            <span class="web-accordion__icon" aria-hidden="true"></span>
        </button>
    </h3>

    <div
        id="{{ $panelId }}"
        role="region"
        aria-labelledby="{{ $triggerId }}"
        class="web-accordion__panel"
        data-web-accordion-panel
        @if (! $expanded) hidden @endif
    >
        <div class="web-accordion__body">{{ $slot }}</div>
    </div>
</div>
