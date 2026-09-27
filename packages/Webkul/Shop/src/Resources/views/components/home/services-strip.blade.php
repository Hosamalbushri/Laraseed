@props([
    'options' => [],
    'ariaLabel' => null,
])

@php
    $services = $options['services'] ?? [];
@endphp

@if ($services !== [])
    <section
        class="border-b border-slate-100 bg-white py-10"
        @if ($ariaLabel) aria-label="{{ $ariaLabel }}" @endif
    >
        <div class="container px-4 lg:px-[60px]">
            <div class="flex flex-wrap justify-center gap-6 max-md:grid max-md:grid-cols-2 max-md:gap-x-4 max-md:text-center">
                @foreach ($services as $service)
                    <div class="flex max-w-xs items-center gap-4 bg-white max-md:grid max-md:justify-center max-md:gap-2">
                        <span
                            class="{{ $service['service_icon'] ?? 'icon-calendar' }} flex h-14 w-14 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white p-2 text-3xl text-[color:var(--shop-accent-hover)] max-md:mx-auto max-sm:h-10 max-sm:w-10 max-sm:text-2xl"
                            role="presentation"
                        ></span>
                        <div>
                            <p class="text-base font-semibold text-slate-900">
                                {{ $service['title'] ?? '' }}
                            </p>
                            <p class="mt-1 max-w-[220px] text-sm text-slate-600 max-md:mx-auto max-md:max-w-none">
                                {{ $service['description'] ?? '' }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
