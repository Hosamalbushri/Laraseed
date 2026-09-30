@props([
    'id' => null,
    'align' => 'start',
    'width' => 'w-56',
])

@php
    $dropdownId = $id ?? 'web-dropdown-' . bin2hex(random_bytes(4));
    $menuId = $dropdownId . '-menu';

    $alignmentClasses = match ($align) {
        'end' => 'ltr:right-0 rtl:left-0 origin-top-right',
        'center' => 'left-1/2 -translate-x-1/2 origin-top',
        default => 'ltr:left-0 rtl:right-0 origin-top-left',
    };
@endphp

<v-web-dropdown
    id="{{ $dropdownId }}"
    data-web-dropdown
    class="relative inline-block text-start"
>
    {{-- Trigger slot / button --}}
    <div
        data-web-dropdown-trigger
        aria-haspopup="true"
        aria-expanded="false"
        aria-controls="{{ $menuId }}"
        class="inline-block cursor-pointer"
    >
        @if (isset($trigger))
            {{ $trigger }}
        @else
            <button
                type="button"
                class="inline-flex items-center justify-between gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-600"
            >
                <span>{{ trans('web::app.common.options') ?? 'Options' }}</span>
                <svg class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        @endif
    </div>

    {{-- Dropdown Menu Popover --}}
    <div
        id="{{ $menuId }}"
        role="region"
        data-web-dropdown-menu
        data-web-dropdown-open="false"
        hidden
        {{ $attributes->merge(['class' => 'absolute z-30 mt-2 ' . $width . ' ' . $alignmentClasses . ' rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl transition-all focus:outline-none text-start']) }}
    >
        {{ $slot }}
    </div>
</v-web-dropdown>
