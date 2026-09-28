@props([
    'as' => 'div',
    'header' => null,
    'footer' => null,
])

@php
    $tag = in_array($as, ['div', 'article', 'section'], true) ? $as : 'div';
@endphp

<{{ $tag }} {{ $attributes->merge(['class' => 'web-card']) }}>
    @if ($header)
        <header class="web-card__header">
            {{ $header }}
        </header>
    @endif

    <div class="web-card__content">
        {{ $slot }}
    </div>

    @if ($footer)
        <footer class="web-card__footer">
            {{ $footer }}
        </footer>
    @endif
</{{ $tag }}>
