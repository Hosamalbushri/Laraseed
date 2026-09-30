@props([
    'id' => null,
    'title' => null,
    'placement' => 'start',
    'open' => false,
])

@php
    $drawerId = $id ?? 'web-drawer-' . bin2hex(random_bytes(4));
    $titleId = $drawerId . '-title';

    $normalizedPlacement = in_array($placement, ['start', 'end', 'left', 'right', 'top', 'bottom'], true) ? $placement : 'start';

    // Map logical placement for RTL/LTR safety
    $positionClasses = match ($normalizedPlacement) {
        'end', 'right' => 'inset-y-0 ltr:right-0 rtl:left-0 max-w-md w-full',
        'top' => 'inset-x-0 top-0 max-h-96 w-full',
        'bottom' => 'inset-x-0 bottom-0 max-h-96 w-full',
        default => 'inset-y-0 ltr:left-0 rtl:right-0 max-w-md w-full', // 'start'
    };
@endphp

<v-web-drawer
    id="{{ $drawerId }}"
    placement="{{ $normalizedPlacement }}"
    data-web-drawer
    class="contents"
>
    @if (isset($trigger))
        <div data-web-drawer-trigger="{{ $drawerId }}" aria-haspopup="dialog" aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="{{ $drawerId }}-dialog" class="inline-block">
            {{ $trigger }}
        </div>
    @endif

    <div
        id="{{ $drawerId }}-dialog"
        role="dialog"
        aria-modal="true"
        @if ($title) aria-labelledby="{{ $titleId }}" @endif
        data-web-drawer-dialog
        data-web-drawer-open="{{ $open ? 'true' : 'false' }}"
        aria-hidden="{{ $open ? 'false' : 'true' }}"
        @if (! $open) hidden @endif
        class="fixed inset-0 z-50 overflow-hidden"
    >
        {{-- Backdrop --}}
        <div
            class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm transition-opacity"
            data-web-drawer-backdrop
            aria-hidden="true"
        ></div>

        {{-- Slideout Surface --}}
        <div
            {{ $attributes->merge(['class' => 'fixed ' . $positionClasses . ' bg-white border-slate-200 shadow-2xl flex flex-col z-10 transition-transform overflow-y-auto text-start']) }}
            data-web-drawer-content
        >
            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <div class="min-w-0 flex-1">
                    @if (isset($header))
                        {{ $header }}
                    @elseif ($title)
                        <h3 id="{{ $titleId }}" class="text-lg font-bold text-slate-900 truncate">{{ $title }}</h3>
                    @endif
                </div>

                <button
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-600 transition-colors"
                    data-web-drawer-close
                    aria-label="{{ trans('web::app.common.close') ?? 'Close' }}"
                >
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="flex-1 px-6 py-4 text-slate-700 overflow-y-auto">
                {{ $slot }}
            </div>

            {{-- Footer --}}
            @if (isset($footer))
                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</v-web-drawer>
