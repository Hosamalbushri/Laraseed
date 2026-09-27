@if ($paginator->hasPages())
    @php
        $showingTotal = method_exists($paginator, 'total');
    @endphp

    <div class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-200 bg-white px-4 py-4 sm:px-6">
        @if ($showingTotal)
            <p class="text-xs font-medium text-slate-600">
                @lang('shop::app.partials.pagination.pagination-showing', [
                    'firstItem' => $paginator->firstItem(),
                    'lastItem' => $paginator->lastItem(),
                    'total' => $paginator->total(),
                ])
            </p>
        @else
            <p class="text-xs font-medium text-slate-600">
                @lang('shop::app.partials.pagination.simple')
            </p>
        @endif

        <nav aria-label="{{ __('shop::app.partials.pagination.page-nav') }}">
            <ul dir="ltr" class="inline-flex items-center gap-0 rounded-lg border border-slate-200 bg-white text-sm shadow-sm">
                <li>
                    @if ($paginator->onFirstPage())
                        <span class="flex min-h-[2.25rem] min-w-[2.25rem] items-center justify-center border-e border-slate-200 px-2 text-slate-300">
                            {{ __('shop::app.partials.pagination.prev-symbol') }}
                        </span>
                    @else
                        <a
                            href="{{ urldecode($paginator->previousPageUrl()) }}"
                            class="flex min-h-[2.25rem] min-w-[2.25rem] items-center justify-center border-e border-slate-200 px-2 font-medium text-slate-700 hover:bg-slate-50"
                            rel="prev"
                            aria-label="{{ __('shop::app.partials.pagination.prev-page') }}"
                        >
                            {{ __('shop::app.partials.pagination.prev-symbol') }}
                        </a>
                    @endif
                </li>

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li>
                            <span class="flex min-h-[2.25rem] min-w-[2.25rem] items-center justify-center border-e border-slate-200 px-2 text-slate-400">
                                {{ $element }}
                            </span>
                        </li>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            <li>
                                @if ($page == $paginator->currentPage())
                                    <span
                                        class="flex min-h-[2.25rem] min-w-[2.25rem] items-center justify-center border-e border-slate-200 bg-[color:var(--shop-surface)] px-2 font-semibold text-[color:var(--shop-accent-hover)]"
                                        aria-current="page"
                                    >
                                        {{ $page }}
                                    </span>
                                @else
                                    <a
                                        href="{{ $url }}"
                                        class="flex min-h-[2.25rem] min-w-[2.25rem] items-center justify-center border-e border-slate-200 px-2 font-medium text-slate-700 hover:bg-slate-50"
                                    >
                                        {{ $page }}
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    @endif
                @endforeach

                <li>
                    @if ($paginator->hasMorePages())
                        <a
                            href="{{ urldecode($paginator->nextPageUrl()) }}"
                            class="flex min-h-[2.25rem] min-w-[2.25rem] items-center justify-center px-2 font-medium text-slate-700 hover:bg-slate-50"
                            rel="next"
                            aria-label="{{ __('shop::app.partials.pagination.next-page') }}"
                        >
                            {{ __('shop::app.partials.pagination.next-symbol') }}
                        </a>
                    @else
                        <span class="flex min-h-[2.25rem] min-w-[2.25rem] items-center justify-center px-2 text-slate-300">
                            {{ __('shop::app.partials.pagination.next-symbol') }}
                        </span>
                    @endif
                </li>
            </ul>
        </nav>
    </div>
@endif
