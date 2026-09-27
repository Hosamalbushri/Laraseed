@php
    $type = $flash['type'] ?? 'info';
    $message = $flash['message'] ?? '';

    $styles = [
        'success' => [
            'container' => 'background: #D4EDDA;',
            'text' => 'color: #155721;',
        ],
        'error' => [
            'container' => 'background: #F8D7DA;',
            'text' => 'color: #721C24;',
        ],
        'warning' => [
            'container' => 'background: #FFF3CD;',
            'text' => 'color: #856404;',
        ],
        'info' => [
            'container' => 'background: #E2E3E5;',
            'text' => 'color: #383D41;',
        ],
    ];
    $s = $styles[$type] ?? $styles['info'];

    $iconClass = match ($type) {
        'success' => 'icon-toast-done',
        'error' => 'icon-toast-error',
        'warning' => 'icon-toast-exclamation-mark',
        default => 'icon-toast-info',
    };
@endphp

<div
    data-shop-flash
    data-auto-dismiss="5000"
    class="flex w-max max-w-[408px] justify-between gap-8 rounded-lg px-5 py-3 shadow-sm max-sm:max-w-full max-sm:gap-3 max-sm:px-4 max-sm:py-3"
    style="{{ $s['container'] }}"
>
    <p
        class="flex min-w-0 flex-1 items-start break-words text-sm"
        style="{{ $s['text'] }}"
    >
        <span
            class="{{ $iconClass }} mt-0.5 text-2xl ltr:mr-2.5 rtl:ml-2.5"
            style="{{ $s['text'] }}"
            aria-hidden="true"
        ></span>
        <span>{!! nl2br(e(is_string($message) ? $message : (string) $message)) !!}</span>
    </p>

    <button
        type="button"
        data-shop-flash-dismiss
        class="icon-cancel max-h-4 max-w-4 shrink-0 cursor-pointer border-0 bg-transparent p-0"
        style="{{ $s['text'] }}"
        aria-label="{{ __('shop::app.components.layouts.flash-group.close') }}"
    >
    </button>
</div>
