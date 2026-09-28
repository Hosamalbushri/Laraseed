@props([
    'variant' => 'info',
    'title' => null,
    'dismissible' => false,
])

@php
    $validVariants = ['info', 'success', 'warning', 'danger'];
    $normalizedVariant = in_array($variant, $validVariants, true) ? $variant : 'info';

    $isUrgent = in_array($normalizedVariant, ['danger', 'warning'], true);
    $role = $isUrgent ? 'alert' : 'status';

    $classes = 'web-alert web-alert--' . $normalizedVariant . ($dismissible ? ' is-dismissible' : '');
@endphp

<div
    role="{{ $role }}"
    {{ $attributes->merge(['class' => $classes]) }}
    @if ($dismissible) data-web-alert @endif
>
    <div class="web-alert__body">
        @if ($title)
            <h4 class="web-alert__title">{{ $title }}</h4>
        @endif

        <div class="web-alert__content">
            {{ $slot }}
        </div>
    </div>

    @if ($dismissible)
        <button
            type="button"
            class="web-alert__close"
            aria-label="{{ trans('web::app.common.close') ?? 'Close' }}"
            data-web-alert-dismiss
        >
            <span aria-hidden="true">&times;</span>
        </button>
    @endif
</div>
