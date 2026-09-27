@props([
    'id' => null,
    'name' => null,
])

<div class="relative">
    <select
        @if ($id) id="{{ $id }}" @endif
        @if ($name) name="{{ $name }}" @endif
        {{ $attributes->merge(['class' => 'custom-select w-full appearance-none rounded-lg border border-[color:var(--shop-border-soft)] bg-white ltr:pr-10 rtl:pl-10 ltr:pl-3 rtl:pr-3 py-2 text-sm text-[color:var(--shop-text)] transition hover:border-[color:var(--shop-border-hover)] focus:border-[color:var(--shop-accent)] focus:ring-2 focus:ring-[color:var(--shop-accent)]/20 focus:outline-none']) }}
    >
        {{ $slot }}
    </select>

    <span class="pointer-events-none absolute top-1/2 -translate-y-1/2 text-[color:var(--shop-accent)] ltr:right-3 rtl:left-3" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.937a.75.75 0 1 1 1.08 1.04l-4.25 4.51a.75.75 0 0 1-1.08 0l-4.25-4.51a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
        </svg>
    </span>
</div>
