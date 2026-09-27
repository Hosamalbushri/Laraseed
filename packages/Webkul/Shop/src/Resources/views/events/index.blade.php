<x-shop::layouts :title="__('shop::app.events.index.title')">
    <div class="container px-4 py-10 max-md:px-4 lg:px-[60px]">
    <nav
        class="mb-6 border-b border-[color:var(--shop-border-soft)] pb-4"
        aria-label="{{ __('shop::app.events.show.breadcrumb-nav') }}"
    >
        <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-[color:var(--shop-text-muted)]">
            <li>
                <a
                    href="{{ route('shop.home.index') }}"
                    class="font-medium text-[color:var(--shop-accent)] transition hover:text-[color:var(--shop-accent-hover)] hover:underline"
                >
                    {{ __('shop::app.events.show.breadcrumb-home') }}
                </a>
            </li>

            <li
                class="text-[color:var(--shop-border-soft)] select-none"
                aria-hidden="true"
            >
                /
            </li>

            <li
                class="min-w-0 max-w-full font-semibold text-[color:var(--shop-text)]"
                aria-current="page"
            >
                {{ __('shop::app.events.index.title') }}
            </li>
        </ol>
    </nav>

    @php
        $activeCategoryId = (int) ($filters['category'] ?? 0);
        $currentQuery = request()->query();
        $hasAdvancedFilters = ($filters['query'] ?? '') !== ''
            || ($filters['availability'] ?? '') !== ''
            || ($filters['date_from'] ?? '') !== ''
            || ($filters['date_to'] ?? '') !== ''
            || (($filters['sort'] ?? 'latest') !== 'latest');
        unset($currentQuery['page']);
    @endphp

    <section class="relative mb-10 overflow-hidden rounded-[28px] border border-violet-100/80 bg-gradient-to-br from-white via-violet-50/65 to-indigo-50/70 px-6 py-8 shadow-[0_20px_50px_-35px_rgba(76,29,149,0.45)] sm:px-8 sm:py-10">
        <div class="pointer-events-none absolute inset-0 opacity-40 [background-image:linear-gradient(rgba(99,102,241,0.08)_1px,transparent_1px),linear-gradient(90deg,rgba(99,102,241,0.08)_1px,transparent_1px)] [background-size:24px_24px]" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -top-16 -end-16 h-56 w-56 rounded-full bg-violet-300/25 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-20 -start-16 h-56 w-56 rounded-full bg-indigo-300/25 blur-3xl" aria-hidden="true"></div>

        <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl text-center lg:text-start">
                <span class="mb-3 inline-flex items-center gap-2 rounded-full border border-[color:var(--shop-primary)]/30 bg-white/80 px-4 py-1.5 text-xs font-bold text-[color:var(--shop-primary)] shadow-sm backdrop-blur">
                    <i class="fas fa-calendar-alt text-[11px] text-[color:var(--shop-icon-color)]" aria-hidden="true"></i>
                    {{ __('shop::app.events.index.title') }}
                </span>

                <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl lg:text-5xl">
                    {{ $pageHeading ?? __('shop::app.events.index.heading') }}
                </h1>

                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-slate-600 sm:text-base lg:mx-0">
                    {{ $pageDescription ?? __('shop::app.events.index.subheading') }}
                </p>
            </div>

            <div class="rounded-2xl p-2">
            <div class="flex flex-wrap items-center justify-center gap-2 lg:justify-end">
                <label
                    for="events-filters-toggle"
                    class="inline-flex items-center gap-2 rounded-xl bg-[color:var(--shop-primary)] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[color:var(--shop-accent)] hover:brightness-[1.02]"
                >
                    <i class="fas fa-sliders-h text-xs" aria-hidden="true"></i>
                    {{ __('shop::app.events.index.filters.button') }}
                </label>

                <span class="inline-flex items-center gap-2 rounded-xl border border-[color:var(--shop-primary)]/30 bg-white px-3.5 py-2 text-sm font-semibold text-[color:var(--shop-primary)]">
                    <i class="fas fa-layer-group text-[color:var(--shop-icon-color)] text-xs" aria-hidden="true"></i>
                    {{ $events->total() }} {{ __('shop::app.events.index.title') }}
                </span>
            </div>
            </div>
        </div>
    </section>

    <form id="events-filters" method="GET" action="{{ route('shop.events.index') }}" class="mb-8">
        <input
            id="events-filters-toggle"
            type="checkbox"
            class="peer sr-only"
            @checked($hasAdvancedFilters)
        >

        <div class="mb-8 rounded-2xl border border-slate-200/80 bg-white/75 p-3 shadow-[0_12px_36px_-20px_rgba(15,23,42,0.35)] backdrop-blur sm:p-4">
            <p class="mb-3 px-1 text-xs font-bold uppercase tracking-[0.12em] text-slate-500">
                {{ __('shop::app.events.index.filters.category-tabs-label') }}
            </p>

            <div class="scrollbar-hide -mx-1 overflow-x-auto px-1 pb-1">
                <div class="flex w-max min-w-full items-stretch gap-2 sm:gap-3">
                    @php
                        $allCategoriesQuery = $currentQuery;
                        unset($allCategoriesQuery['category']);
                    @endphp

                    <a
                        href="{{ route('shop.events.index', $allCategoriesQuery) }}"
                        class="{{ $activeCategoryId === 0 ? 'events-tab-active border-[color:var(--shop-primary)]/35 bg-[color:var(--shop-badge-color)]/15 text-[color:var(--shop-primary)] shadow-[0_12px_30px_-20px_color-mix(in_srgb,var(--shop-primary)_45%,transparent)] hover:border-[color:var(--shop-primary)]/55 hover:bg-[color:var(--shop-badge-color)]/25 hover:text-[color:var(--shop-primary)]' : 'border-slate-200 bg-white text-slate-700 hover:border-[color:var(--shop-primary)]/40 hover:bg-[color:var(--shop-badge-color)]/10 hover:text-[color:var(--shop-primary)]' }} group inline-flex min-w-[176px] shrink-0 items-center gap-3 rounded-xl border px-3 py-2.5 text-sm font-semibold transition duration-200"
                    >
                        <span class="{{ $activeCategoryId === 0 ? 'bg-[color:var(--shop-primary)] text-white group-hover:bg-[color:var(--shop-accent)]' : 'bg-slate-100 text-slate-600 group-hover:bg-[color:var(--shop-badge-color)]/20 group-hover:text-[color:var(--shop-icon-color)]' }} inline-flex h-9 w-9 items-center justify-center rounded-lg text-[11px] font-bold transition">
                            {{ __('shop::app.events.index.filters.all-short') }}
                        </span>
                        <span class="line-clamp-1">{{ __('shop::app.events.index.filters.all-categories') }}</span>
                    </a>

                    @foreach (($categories ?? collect()) as $category)
                        @php
                            $categoryName = trim((string) $category->name);
                            $categoryInitial = $categoryName !== '' ? \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($categoryName, 0, 1)) : '—';
                        @endphp
                        <a
                            href="{{ route('shop.events.index', array_merge($currentQuery, ['category' => $category->id])) }}"
                            class="{{ $activeCategoryId === (int) $category->id ? 'events-tab-active border-[color:var(--shop-primary)]/35 bg-[color:var(--shop-badge-color)]/15 text-[color:var(--shop-primary)] shadow-[0_12px_30px_-20px_color-mix(in_srgb,var(--shop-primary)_45%,transparent)] hover:border-[color:var(--shop-primary)]/55 hover:bg-[color:var(--shop-badge-color)]/25 hover:text-[color:var(--shop-primary)]' : 'border-slate-200 bg-white text-slate-700 hover:border-[color:var(--shop-primary)]/40 hover:bg-[color:var(--shop-badge-color)]/10 hover:text-[color:var(--shop-primary)]' }} group inline-flex min-w-[176px] shrink-0 items-center gap-3 rounded-xl border px-3 py-2.5 text-sm font-semibold transition duration-200"
                        >
                            <span class="{{ $activeCategoryId === (int) $category->id ? 'bg-[color:var(--shop-primary)] text-white group-hover:bg-[color:var(--shop-accent)]' : 'bg-slate-100 text-slate-600 group-hover:bg-[color:var(--shop-badge-color)]/20 group-hover:text-[color:var(--shop-icon-color)]' }} inline-flex h-9 w-9 items-center justify-center rounded-lg text-xs font-bold transition">
                                {{ $categoryInitial }}
                            </span>
                            <span class="line-clamp-1">{{ $category->name }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div
            id="events-advanced-filters"
            class="mb-2 hidden rounded-2xl border border-slate-200/80 bg-white/85 p-4 shadow-[0_10px_35px_-24px_rgba(15,23,42,0.45)] backdrop-blur-sm peer-checked:block"
        >
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <x-shop::form.label for="query">
                        {{ __('shop::app.events.index.filters.search') }}
                    </x-shop::form.label>

                    <x-shop::form.input
                        id="query"
                        name="query"
                        :value="$filters['query'] ?? ''"
                        placeholder="{{ __('shop::app.events.index.filters.search-placeholder') }}"
                    />
                </div>

                <div>
                    <x-shop::form.label for="availability">
                        {{ __('shop::app.events.index.filters.availability') }}
                    </x-shop::form.label>

                    <x-shop::form.select id="availability" name="availability">
                        <option value="">{{ __('shop::app.events.index.filters.all-statuses') }}</option>
                        <option value="open" @selected(($filters['availability'] ?? '') === 'open')>{{ __('shop::app.events.index.filters.open') }}</option>
                        <option value="sold_out" @selected(($filters['availability'] ?? '') === 'sold_out')>{{ __('shop::app.events.index.filters.sold-out') }}</option>
                        <option value="ended" @selected(($filters['availability'] ?? '') === 'ended')>{{ __('shop::app.events.index.filters.ended') }}</option>
                    </x-shop::form.select>
                </div>

                <div>
                    <x-shop::form.label for="date_from">
                        {{ __('shop::app.events.index.filters.date-from') }}
                    </x-shop::form.label>

                    <x-shop::form.input
                        id="date_from"
                        type="date"
                        name="date_from"
                        :value="$filters['date_from'] ?? ''"
                    />
                </div>

                <div>
                    <x-shop::form.label for="date_to">
                        {{ __('shop::app.events.index.filters.date-to') }}
                    </x-shop::form.label>

                    <x-shop::form.input
                        id="date_to"
                        type="date"
                        name="date_to"
                        :value="$filters['date_to'] ?? ''"
                    />
                </div>

                <div>
                    <x-shop::form.label for="sort">
                        {{ __('shop::app.events.index.filters.sort') }}
                    </x-shop::form.label>

                    <x-shop::form.select id="sort" name="sort">
                        <option value="latest" @selected(($filters['sort'] ?? 'latest') === 'latest')>{{ __('shop::app.events.index.filters.sort-latest') }}</option>
                        <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>{{ __('shop::app.events.index.filters.sort-oldest') }}</option>
                        <option value="most_subscribed" @selected(($filters['sort'] ?? '') === 'most_subscribed')>{{ __('shop::app.events.index.filters.sort-most-subscribed') }}</option>
                        <option value="title_asc" @selected(($filters['sort'] ?? '') === 'title_asc')>{{ __('shop::app.events.index.filters.sort-title-asc') }}</option>
                        <option value="title_desc" @selected(($filters['sort'] ?? '') === 'title_desc')>{{ __('shop::app.events.index.filters.sort-title-desc') }}</option>
                    </x-shop::form.select>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-3">
                <button type="submit" class="rounded-lg bg-[color:var(--shop-primary)] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[color:var(--shop-primary-hover)]">
                    {{ __('shop::app.events.index.filters.apply') }}
                </button>

                <a href="{{ route('shop.events.index') }}" class="rounded-lg border border-[color:var(--shop-border-soft)] px-4 py-2 text-sm font-medium text-[color:var(--shop-text)] transition hover:bg-[color:var(--shop-surface)]">
                    {{ __('shop::app.events.index.filters.reset') }}
                </a>
            </div>
        </div>

        <input type="hidden" name="category" value="{{ $activeCategoryId }}">
    </form>

    @if ($events->isEmpty())
        <div class="rounded-2xl border border-dashed border-[color:var(--shop-border-soft)] bg-white px-6 py-16 text-center">
            <p class="text-[color:var(--shop-text-muted)]">{{ __('shop::app.events.index.empty') }}</p>
        </div>
    @else
        <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 lg:gap-8">
            @foreach ($events as $event)
                <li class="min-w-0">
                    @include('shop::events.partials.event-card', [
                        'event' => $event,
                        'variant' => 'grid',
                        'showExcerpt' => true,
                        'subscribedEventIds' => $subscribedEventIds ?? [],
                    ])
                </li>
            @endforeach
        </ul>

        <div class="mt-8">
            {{ $events->withQueryString()->links() }}
        </div>
    @endif
    </div>
</x-shop::layouts>

@pushOnce('styles', 'shop-events-premium-ui')
    <style>
        .events-tab-active {
            position: relative;
            overflow: hidden;
        }
        .events-tab-active::after {
            content: "";
            position: absolute;
            inset-inline-end: -35%;
            top: -120%;
            width: 35%;
            height: 300%;
            transform: rotate(18deg);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.0) 0%, rgba(255, 255, 255, 0.45) 45%, rgba(255, 255, 255, 0.0) 100%);
            animation: events-tab-shimmer 4s linear infinite;
            pointer-events: none;
        }
        @keyframes events-tab-shimmer {
            from { inset-inline-end: -35%; }
            to { inset-inline-end: 130%; }
        }
    </style>
@endPushOnce
