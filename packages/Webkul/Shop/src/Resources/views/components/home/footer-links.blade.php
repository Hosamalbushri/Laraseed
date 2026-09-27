@props([
    'options' => [],
    'ariaLabel' => null,
])

@php
    $sections = $options['sections'] ?? [];
@endphp

@if ($sections !== [])
    <section
        class="border-b border-slate-100 bg-slate-50 py-10"
        @if (! empty($ariaLabel)) aria-label="{{ $ariaLabel }}" @endif
    >
        <div class="container px-4 lg:px-[60px]">
            <div class="flex flex-wrap items-start gap-10 max-md:flex-col max-md:gap-8">
                @foreach ($sections as $section)
                    @php
                        $links = $section['links'] ?? [];
                        if ($links === []) {
                            continue;
                        }
                        usort($links, fn ($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));
                    @endphp
                    <ul class="grid min-w-[140px] gap-3 text-sm text-slate-700">
                        @foreach ($links as $link)
                            <li>
                                <a
                                    href="{{ $link['url'] ?: '#' }}"
                                    class="font-medium hover:text-[color:var(--shop-accent)]"
                                >
                                    {{ $link['title'] ?? '' }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endforeach
            </div>
        </div>
    </section>
@endif
