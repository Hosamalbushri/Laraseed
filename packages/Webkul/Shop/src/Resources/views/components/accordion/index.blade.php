@props([
    'isActive' => false,
])

<div {{ $attributes->merge(['class' => 'border-b border-zinc-200']) }}>
    <details
        class="group"
        @if ($isActive) open @endif
    >
        @isset($header)
            <summary
                {{ $header->attributes->class('flex cursor-pointer select-none list-none items-center justify-between p-4 marker:hidden [&::-webkit-details-marker]:hidden') }}
            >
                {{ $header }}

                <span
                    class="icon-arrow-down shrink-0 text-2xl transition-transform duration-200 group-open:rotate-180"
                    aria-hidden="true"
                ></span>
            </summary>
        @endisset

        @isset($content)
            <div {{ $content->attributes->merge(['class' => 'z-10 rounded-lg bg-white p-1.5']) }}>
                {{ $content }}
            </div>
        @endisset
    </details>
</div>
