@props([
    'id' => null,
    'title' => null,
    'size' => 'md',
    'open' => false,
])

@php
    $modalId = $id ?? 'web-modal-' . bin2hex(random_bytes(4));
    $titleId = $modalId . '-title';

    $sizeClasses = match ($size) {
        'sm' => 'max-w-md',
        'lg' => 'max-w-2xl',
        'xl' => 'max-w-4xl',
        'full' => 'max-w-full m-4',
        default => 'max-w-lg',
    };
@endphp

<v-web-modal
    id="{{ $modalId }}"
    data-web-modal
    class="contents"
>
    @if (isset($trigger))
        <div data-web-modal-trigger="{{ $modalId }}" aria-haspopup="dialog" aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="{{ $modalId }}-dialog" class="inline-block">
            {{ $trigger }}
        </div>
    @endif

    <div
        id="{{ $modalId }}-dialog"
        role="dialog"
        aria-modal="true"
        @if ($title) aria-labelledby="{{ $titleId }}" @endif
        data-web-modal-dialog
        data-web-modal-open="{{ $open ? 'true' : 'false' }}"
        aria-hidden="{{ $open ? 'false' : 'true' }}"
        @if (! $open) hidden @endif
        class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto"
    >
        {{-- Backdrop --}}
        <div
            class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm transition-opacity"
            data-web-modal-backdrop
            aria-hidden="true"
        ></div>

        {{-- Dialog Surface --}}
        <div
            {{ $attributes->merge(['class' => 'relative w-full ' . $sizeClasses . ' bg-white rounded-2xl border border-slate-200 shadow-2xl overflow-hidden z-10 my-8 text-start transition-all transform']) }}
            data-web-modal-content
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
                    data-web-modal-close
                    aria-label="{{ trans('web::app.common.close') ?? 'Close' }}"
                >
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="px-6 py-4 text-slate-700">
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
</v-web-modal>
