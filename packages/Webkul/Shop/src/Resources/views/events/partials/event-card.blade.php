@php
    $variant = $variant ?? 'grid';
    $showExcerpt = $showExcerpt ?? false;
    $allowUnsubscribe = $allowUnsubscribe ?? false;
    $detailUrl = route('shop.events.show', $event->id);
    $canSubscribe = $event->isCurrentlyAvailable();
    $eventTitleAttr = e($event->title);
    $paddingClass = $variant === 'carousel' ? 'p-4' : 'p-5';
    $subscribedEventIds = isset($subscribedEventIds) ? array_map('intval', (array) $subscribedEventIds) : [];
    $isSubscribed = in_array((int) $event->id, $subscribedEventIds, true);
    // Ended = current calendar day is strictly after event_end_date (when set).
    $isEnded = $event->event_end_date
        && \Carbon\Carbon::today()->startOfDay()->gt(
            \Carbon\Carbon::parse($event->event_end_date)->startOfDay()
        );
    $disableSubscribe = (! $canSubscribe) || $isEnded;
    $studentLoginUrl = Route::has('student.login')
        ? route('student.login', ['intended' => route('shop.events.show', $event->id)])
        : '';
    $subscribePostUrl = Route::has('shop.events.subscribe') ? route('shop.events.subscribe', $event->id) : '';
    $unsubscribePostUrl = ($allowUnsubscribe && Route::has('shop.events.unsubscribe'))
        ? route('shop.events.unsubscribe', $event->id)
        : '';

    $categories = $event->categories ?? collect();
    $attendeesCount = (int) ($event->subscribers_count ?? 0);
    $remainingSeats = ($event->availability_use_seats && $event->available_seats !== null)
        ? max(0, (int) $event->available_seats)
        : null;
    $totalSeats = $remainingSeats !== null ? ($attendeesCount + $remainingSeats) : null;
    $progressPct = ($totalSeats && $totalSeats > 0)
        ? max(0, min(100, (int) round(($attendeesCount / $totalSeats) * 100)))
        : null;
    $eventTime = $event->event_date ? $event->event_date->translatedFormat('l') : '';
    $eventLocation = trim((string) ($event->location ?? ''));
    $eventOrganizer = trim((string) ($event->organizer ?? ''));
@endphp

@once
    @include('shop::events.partials.event-subscribe-dialog')
@endonce

@if ($allowUnsubscribe)
    @once
        @include('shop::events.partials.event-unsubscribe-dialog')
    @endonce
@endif

<article
    @class([
        'group flex h-full flex-col overflow-hidden rounded-3xl border border-[#e8ecf3] bg-white shadow-[0_12px_28px_-12px_rgba(2,6,23,0.20)] transition duration-300 hover:-translate-y-2 hover:shadow-[0_26px_45px_-18px_rgba(2,6,23,0.28)]',
        'w-[min(100%,300px)] shrink-0' => $variant === 'carousel',
    ])
>
    <div class="relative shrink-0 overflow-hidden rounded-t-3xl">
        <a
            href="{{ $detailUrl }}"
            class="relative block focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[color:var(--shop-ring)]"
        >
            @if ($event->image)
                <img
                    src="{{ Storage::url($event->image) }}"
                    alt=""
                    class="aspect-[16/10] w-full object-cover transition duration-500 group-hover:scale-110"
                >
            @else
                <div class="flex aspect-[16/10] w-full items-center justify-center bg-gradient-to-br from-[color:var(--shop-surface-strong)] via-[color:var(--shop-surface)] to-[color:var(--shop-surface-strong)] text-[color:var(--shop-placeholder)]">
                    <span class="text-xs font-semibold tracking-wide">{{ __('shop::app.events.card.no-image') }}</span>
                </div>
            @endif

            <div class="pointer-events-none absolute inset-0 bg-[color:var(--shop-badge-color)]/20 opacity-0 transition duration-300 group-hover:opacity-100" aria-hidden="true"></div>

            @if ($isEnded)
                <span class="pointer-events-none absolute start-3 top-3 inline-flex items-center rounded-full bg-red-600 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-white shadow-sm">
                    {{ __('shop::app.events.seats.ended') }}
                </span>
            @endif

            @if ($categories->isNotEmpty())
                <div class="pointer-events-none absolute end-3 top-3 z-[2] flex max-w-[82%] flex-wrap justify-end gap-1.5">
                    @foreach ($categories as $cat)
                        @php $catName = trim((string) ($cat->name ?? '')); @endphp
                        @if ($catName !== '')
                            <span class="inline-flex items-center rounded-full bg-[color:var(--shop-badge-color)] px-2.5 py-1 text-[11px] font-bold text-white shadow-sm">
                                {{ $catName }}
                            </span>
                        @endif
                    @endforeach
                </div>
            @endif

            @if ($event->event_date)
                <div class="pointer-events-none absolute bottom-3 start-3 end-3">
                    <time
                        datetime="{{ $event->event_date->format('Y-m-d') }}"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-black/70 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur-sm"
                    >
                        <i class="fas fa-calendar-alt text-[11px] text-white" aria-hidden="true"></i>
                        {{ $event->event_date->translatedFormat(__('shop::app.events.card.date-format')) }}
                    </time>
                </div>
            @endif
        </a>
    </div>

    <div class="flex flex-1 flex-col gap-4 {{ $paddingClass }}">

        <h3 @class(['font-bold tracking-tight text-slate-800', 'text-base' => $variant === 'carousel', 'text-xl' => $variant !== 'carousel'])>
            <a
                href="{{ $detailUrl }}"
                class="transition hover:text-[color:var(--shop-accent)] focus:outline-none focus-visible:text-[color:var(--shop-accent)] focus-visible:underline"
            >
                {{ $event->title }}
            </a>
        </h3>

        <div class="space-y-2 text-sm text-slate-500">
            @if ($eventTime !== '')
                <div class="flex items-center gap-2">
                    <i class="fas fa-clock w-4 text-[color:var(--shop-icon-color)]" aria-hidden="true"></i>
                    <span>{{ $eventTime }}</span>
                </div>
            @endif

            @if ($eventLocation !== '')
                <div class="flex items-center gap-2">
                    <i class="fas fa-map-marker-alt w-4 text-[color:var(--shop-icon-color)]" aria-hidden="true"></i>
                    <span class="line-clamp-1">{{ $eventLocation }}</span>
                </div>
            @endif

            @if ($eventOrganizer !== '')
                <div class="flex items-center gap-2">
                    <i class="fas fa-user-tie w-4 text-[color:var(--shop-icon-color)]" aria-hidden="true"></i>
                    <span class="line-clamp-1">{{ $eventOrganizer }}</span>
                </div>
            @endif
        </div>

        @if ($showExcerpt && $event->description)
            <p class="line-clamp-2 flex-1 text-sm leading-relaxed text-slate-500">
                {{ Str::limit(strip_tags($event->description), 160) }}
            </p>
        @endif

        <div class="rounded-xl border border-[#eef1f6] bg-[#fafbfe] p-3">
            <div class="mb-2 flex items-center justify-between text-xs text-slate-500">
                <span>{{ __('shop::app.events.show.seats-heading') }}</span>
                <span>{{ $attendeesCount }} {{ __('shop::app.events.card.attendees') }}</span>
            </div>
            <div class="h-1.5 overflow-hidden rounded-full bg-[#e7ebf3]">
                <div
                    class="h-full rounded-full bg-[color:var(--shop-badge-color)] transition-all duration-500"
                    style="width: {{ $progressPct ?? 0 }}%;"
                ></div>
            </div>
            <div class="mt-2 text-xs font-semibold text-slate-600">
                @include('shop::events.partials.seats-label', ['event' => $event])
            </div>
        </div>

        <div class="mt-auto flex flex-col gap-2.5 border-t border-[#eef1f6] pt-4 sm:flex-row sm:items-stretch">
            <a
                href="{{ $detailUrl }}"
                class="inline-flex min-h-[44px] flex-1 items-center justify-center rounded-full border border-[color:var(--shop-primary)] bg-white px-4 py-2.5 text-center text-sm font-semibold text-[color:var(--shop-primary)] shadow-sm transition hover:bg-[color:var(--shop-primary)] hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--shop-ring)]"
            >
                {{ __('shop::app.events.card.details') }}
            </a>

            @if ($isSubscribed)
                @if ($allowUnsubscribe && $unsubscribePostUrl !== '' && ! $isEnded)
                    <button
                        type="button"
                        class="inline-flex min-h-[44px] flex-1 items-center justify-center rounded-full bg-red-600 px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 active:bg-red-800"
                        data-shop-event-unsubscribe
                        data-unsubscribe-url="{{ e($unsubscribePostUrl) }}"
                        data-event-title="{{ $eventTitleAttr }}"
                    >
                        {{ __('shop::app.student.events.unsubscribe-cta') }}
                    </button>
                @else
                    <span
                        class="inline-flex min-h-[44px] flex-1 cursor-default items-center justify-center rounded-full border border-[color:var(--shop-border-soft)] bg-[color:var(--shop-surface)] px-4 py-2.5 text-center text-sm font-semibold text-[color:var(--shop-accent)]"
                        role="status"
                    >
                        {{ __('shop::app.events.card.subscribe-registered') }}
                    </span>
                @endif
            @else
                <button
                    type="button"
                    class="inline-flex min-h-[44px] flex-1 items-center justify-center rounded-full bg-[color:var(--shop-primary)] px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-[color:var(--shop-accent)] hover:brightness-[1.02] focus:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--shop-ring)] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:bg-[color:var(--shop-border-soft)] disabled:text-[color:var(--shop-text-muted)] disabled:shadow-none"
                    data-shop-event-subscribe
                    data-student-auth="{{ auth('student')->check() ? '1' : '0' }}"
                    data-student-login-url="{{ e($studentLoginUrl) }}"
                    data-subscribe-url="{{ e($subscribePostUrl) }}"
                    data-event-title="{{ $eventTitleAttr }}"
                    data-event-url="{{ e($detailUrl) }}"
                    @if ($disableSubscribe) disabled aria-disabled="true" @endif
                >
                    {{ $canSubscribe ? __('shop::app.events.card.subscribe') : __('shop::app.events.card.subscribe-unavailable') }}
                </button>
            @endif
        </div>
    </div>
</article>
