@php
    $detailUrl = route('shop.events.show', $event->id);
    $canSubscribe = $event->isCurrentlyAvailable();
    $isSubscribedToCurrentEvent = $isSubscribedToCurrentEvent ?? false;
    $studentLoginUrl = Route::has('student.login')
        ? route('student.login', ['intended' => $detailUrl])
        : '';
    $subscribePostUrl = Route::has('shop.events.subscribe') ? route('shop.events.subscribe', $event->id) : '';
    $eventImages = ($event->images ?? collect())
        ->pluck('path')
        ->filter()
        ->values();

    if ($eventImages->isEmpty() && $event->image) {
        $eventImages = collect([$event->image]);
    }

    $toPublicUrl = function ($path) {
        $path = trim((string) $path);

        if ($path === '') {
            return '';
        }

        if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        if (\Illuminate\Support\Str::startsWith($path, ['/'])) {
            return $path;
        }

        return Storage::url($path);
    };

    $eventImageUrls = $eventImages->map($toPublicUrl)->filter()->values();
    $mainEventImageUrl = $eventImageUrls->first();

    $categories = $event->categories ?? collect();
    $firstCategory = $categories->first();
    $firstCategoryName = trim((string) ($firstCategory->name ?? ''));

    $eventOrganizer = trim((string) ($event->organizer ?? ''));
    $eventLocation = trim((string) ($event->location ?? ''));
    if ($eventLocation === '') {
        $eventLocation = $eventOrganizer;
    }

    $eventDate = $event->event_date
        ? $event->event_date->translatedFormat(__('shop::app.events.card.date-format'))
        : '';

    $eventEndDate = $event->event_end_date
        ? $event->event_end_date->translatedFormat(__('shop::app.events.card.date-format'))
        : '';

    $useSeats = (bool) ($event->availability_use_seats ?? true);
    $remainingSeats = null;
    if ($useSeats && $event->available_seats !== null) {
        $remainingSeats = max(0, (int) $event->available_seats);
    }

    $attendeesCount = (int) ($event->subscribers_count ?? 0);
    $totalSeats = $remainingSeats !== null ? ($attendeesCount + $remainingSeats) : null;
    $progressPct = null;
    if ($totalSeats && $totalSeats > 0) {
        $progressPct = (int) round(($attendeesCount / $totalSeats) * 100);
    }

    // Pick up to 4 fields to mimic the "details list" layout.
    $detailFields = ($event->fields ?? collect())
        ->filter(fn ($f) => $f && $f->value !== null && trim((string) $f->value) !== '')
        ->take(4);
@endphp

<x-shop::layouts
    :title="$event->title.' — '.__('shop::app.meta.title')"
    :has-feature="false"
>
    @pushOnce('styles', 'shop-event-details-design-styles')
        <style id="shop-event-details-design-styles">
            .shop-event-details-page {
                background: color-mix(in srgb, var(--shop-badge-color) 8%, #f5f7fa);
                min-height: 100vh;
                padding: 48px 0 72px;
            }
            .shop-event-details-container {
                max-width: 1400px;
                margin: 0 auto;
                padding: 0 20px;
            }
            .shop-event-back {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                color: #6b7280;
                text-decoration: none;
                font-weight: 600;
                transition: color .2s ease;
                margin-bottom: 26px;
            }
            .shop-event-back:hover { color: var(--shop-accent); }

            .shop-event-details-grid {
                display: grid;
                gap: 40px;
            }

            .shop-event-info {
                background: white;
                border-radius: 28px;
                overflow: hidden;
                box-shadow: 0 4px 20px rgba(0,0,0,0.05);
                border: 1px solid #e5e7eb;
            }

            .shop-event-slider {
                position: relative;
            }

            .shop-event-slide-btn {
                position: absolute;
                top: 50%;
                transform: translateY(-50%);
                width: 44px;
                height: 44px;
                border-radius: 999px;
                background: rgba(17, 24, 39, 0.45);
                border: 1px solid rgba(255, 255, 255, 0.25);
                color: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                z-index: 3;
                backdrop-filter: blur(10px);
                transition: transform .2s ease, background .2s ease, border-color .2s ease;
            }
            .shop-event-slide-btn:hover {
                background: color-mix(in srgb, var(--shop-primary) 60%, transparent);
                border-color: rgba(255, 255, 255, 0.40);
                transform: translateY(-50%) scale(1.03);
            }
            .shop-event-slide-prev { left: 16px; }
            .shop-event-slide-next { right: 16px; }

            .shop-event-image {
                position: relative;
                height: 400px;
                overflow: hidden;
            }
            .shop-event-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                transition: opacity .2s ease;
            }
            .shop-event-category-badge {
                position: absolute;
                top: 20px;
                right: 20px;
                background: var(--shop-primary);
                padding: 8px 16px;
                border-radius: 50px;
                color: white;
                font-size: 14px;
                font-weight: 700;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                z-index: 2;
            }

            .shop-event-category-badges {
                position: absolute;
                top: 18px;
                right: 18px;
                left: 18px;
                z-index: 2;
                display: flex;
                flex-wrap: wrap;
                justify-content: flex-end;
                gap: 8px;
                pointer-events: none;
            }

            .shop-event-category-pill {
                pointer-events: none;
                background: var(--shop-badge-color);
                padding: 7px 14px;
                border-radius: 999px;
                color: white;
                font-size: 13px;
                font-weight: 800;
                display: inline-flex;
                align-items: center;
                box-shadow: 0 8px 22px rgba(17, 24, 39, 0.12);
                max-width: 100%;
            }

            .shop-event-category-pill span {
                display: inline-block;
                max-width: 100%;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .shop-event-slider-fade {
                opacity: 0.6 !important;
            }

            .shop-event-thumbs {
                display: flex;
                gap: 10px;
                overflow-x: auto;
                border-top: 1px solid #e5e7eb;
                background: #f9fafb;
                padding: 12px 14px;
            }
            .shop-event-thumb {
                flex: 0 0 auto;
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                overflow: hidden;
                padding: 0;
                background: transparent;
                cursor: pointer;
                transition: border-color .2s ease, transform .2s ease;
            }
            .shop-event-thumb:hover { border-color: var(--shop-accent); transform: translateY(-2px); }
            .shop-event-thumb--active { border-color: var(--shop-primary); }
            .shop-event-thumb img {
                height: 64px;
                width: 92px;
                object-fit: cover;
                display: block;
            }

            .shop-event-content {
                padding: 30px;
            }
            .shop-event-title {
                font-size: 2rem;
                font-weight: 800;
                color: #1f2937;
                margin-bottom: 16px;
            }
            .shop-event-stats {
                display: flex;
                gap: 24px;
                flex-wrap: wrap;
                margin-bottom: 24px;
                padding-bottom: 24px;
                border-bottom: 1px solid #f3f4f6;
            }
            .shop-event-stat {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                color: #6b7280;
                font-size: 14px;
            }
            .shop-event-stat i { color: var(--shop-icon-color); width: 18px; text-align: center; }

            .shop-event-price-tag {
                background: #f3f4f6;
                padding: 6px 12px;
                border-radius: 50px;
                font-weight: 800;
                color: #4b5563;
                white-space: nowrap;
            }

            .shop-event-section-title {
                font-size: 1.2rem;
                font-weight: 800;
                color: #1f2937;
                margin-bottom: 14px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .shop-event-section-title i { color: var(--shop-icon-color); }

            .shop-event-description {
                color: #6b7280;
                line-height: 1.9;
                margin-bottom: 28px;
            }

            .shop-event-details-list {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
                margin-bottom: 26px;
                background: #f9fafb;
                padding: 20px;
                border-radius: 20px;
            }
            .shop-event-detail-item {
                display: flex;
                align-items: center;
                gap: 14px;
            }
            .shop-event-detail-icon {
                width: 44px;
                height: 44px;
                background: white;
                border-radius: 14px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: var(--shop-icon-color);
                border: 1px solid #f1f1f1;
            }
            .shop-event-detail-info h4 {
                font-size: 13px;
                color: #9ca3af;
                margin-bottom: 4px;
                font-weight: 800;
            }
            .shop-event-detail-info p {
                font-size: 14px;
                font-weight: 800;
                color: #1f2937;
            }

            .shop-event-organizer {
                display: flex;
                align-items: center;
                gap: 16px;
                padding: 20px;
                background: #f9fafb;
                border-radius: 20px;
            }
            .shop-event-organizer-avatar {
                width: 62px;
                height: 62px;
                background: var(--shop-primary);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 24px;
                color: white;
            }
            .shop-event-organizer-info h4 {
                font-size: 16px;
                font-weight: 900;
                color: #1f2937;
                margin-bottom: 4px;
            }
            .shop-event-organizer-info p {
                font-size: 13px;
                color: #6b7280;
                font-weight: 700;
            }

            .shop-event-cta {
                margin: 18px 0 26px;
                background: #f9fafb;
                border: 1px solid #eef1f6;
                border-radius: 22px;
                padding: 18px;
            }
            .shop-event-cta-top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 14px;
                flex-wrap: wrap;
            }
            .shop-event-cta-meta {
                display: flex;
                align-items: baseline;
                gap: 12px;
                flex-wrap: wrap;
            }
            .shop-event-cta-number {
                font-size: 1.8rem;
                font-weight: 950;
                color: var(--shop-primary);
                line-height: 1;
            }
            .shop-event-cta-text {
                font-weight: 900;
                color: #374151;
            }
            .shop-event-cta-sub {
                color: #6b7280;
                font-size: 13px;
                font-weight: 800;
            }
            .shop-event-cta-bar {
                height: 10px;
                background: #e5e7eb;
                border-radius: 999px;
                overflow: hidden;
                margin-top: 14px;
            }
            .shop-event-cta-fill {
                height: 100%;
                background: var(--shop-primary);
                border-radius: 999px;
            }
            .shop-event-cta-action {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                min-height: 48px;
                padding: 12px 18px;
                border-radius: 16px;
                font-weight: 950;
                font-size: 15px;
                border: none;
                cursor: pointer;
                transition: transform .2s ease, box-shadow .2s ease, opacity .2s ease;
                background: var(--shop-primary);
                color: #fff;
                white-space: nowrap;
            }
            .shop-event-cta-action:hover:not(:disabled) {
                transform: translateY(-2px);
                box-shadow: 0 10px 25px color-mix(in srgb, var(--shop-primary) 30%, transparent);
            }
            .shop-event-cta-action:disabled {
                cursor: not-allowed;
                opacity: 0.65;
                transform: none;
            }
            .shop-event-cta-status {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 48px;
                padding: 12px 18px;
                border-radius: 16px;
                font-weight: 950;
                font-size: 15px;
                border: 1px solid #e5e7eb;
                background: #fff;
                color: var(--shop-primary);
                white-space: nowrap;
            }

            .shop-suggested-section {
                margin-top: 60px;
                padding-top: 40px;
                border-top: 2px solid #e5e7eb;
            }
            .shop-suggested-header {
                display: flex;
                justify-content: space-between;
                align-items: baseline;
                gap: 16px;
                margin-bottom: 26px;
            }
            .shop-suggested-header h2 {
                font-size: 1.6rem;
                font-weight: 1000;
                color: #1f2937;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .shop-suggested-subtitle {
                color: #6b7280;
                font-weight: 800;
                font-size: 14px;
                margin-top: 6px;
            }

            .shop-suggested-viewall {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                padding: 10px 14px;
                border-radius: 12px;
                font-weight: 950;
                color: #fff;
                background: var(--shop-primary);
                border: 1px solid var(--shop-primary);
                text-decoration: none;
                transition: box-shadow .2s ease, background .2s ease, border-color .2s ease;
                white-space: nowrap;
            }
            .shop-suggested-viewall:hover {
                box-shadow: 0 10px 25px color-mix(in srgb, var(--shop-primary) 20%, transparent);
                background: var(--shop-accent);
                border-color: var(--shop-accent);
            }

            .shop-suggested-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
                gap: 22px;
            }

            .shop-suggested-card {
                background: white;
                border-radius: 20px;
                overflow: hidden;
                transition: all .25s ease;
                border: 1px solid #e5e7eb;
                cursor: pointer;
                text-decoration: none;
                color: inherit;
                display: block;
                position: relative;
            }
            .shop-suggested-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 15px 30px rgba(0,0,0,0.10);
                border-color: var(--shop-primary);
            }

            .shop-suggested-image {
                position: relative;
                height: 160px;
            }
            .shop-suggested-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
                transition: transform .35s ease;
            }
            .shop-suggested-card:hover .shop-suggested-image img {
                transform: scale(1.05);
            }

            .shop-suggested-image::after {
                content: "";
                position: absolute;
                inset: 0;
                background: color-mix(in srgb, var(--shop-badge-color) 18%, transparent);
                opacity: 0;
                transition: opacity .25s ease;
                pointer-events: none;
            }
            .shop-suggested-card:hover .shop-suggested-image::after {
                opacity: 1;
            }
            .shop-suggested-category {
                position: absolute;
                top: 12px;
                right: 12px;
                background: var(--shop-badge-color);
                padding: 6px 12px;
                border-radius: 50px;
                font-size: 11px;
                color: white;
                font-weight: 900;
                display: inline-flex;
                align-items: center;
            }

            .shop-suggested-content { padding: 16px; }
            .shop-suggested-title {
                font-size: 1.05rem;
                font-weight: 900;
                color: #1f2937;
                margin-bottom: 8px;
                line-height: 1.35;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
            .shop-suggested-date {
                font-size: 13px;
                color: #6b7280;
                display: flex;
                align-items: center;
                gap: 8px;
                margin-bottom: 10px;
                font-weight: 800;
            }
            .shop-suggested-price {
                font-size: 13px;
                font-weight: 900;
                color: var(--shop-primary);
            }
            .shop-suggested-price span { color: inherit; }

            .shop-suggested-actions {
                margin-top: 14px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
            }

            .shop-suggested-cta {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                min-height: 42px;
                padding: 10px 14px;
                border-radius: 999px;
                font-weight: 950;
                font-size: 13px;
                background: var(--shop-primary);
                color: #fff;
                box-shadow: 0 10px 25px color-mix(in srgb, var(--shop-primary) 20%, transparent);
                width: 100%;
                text-decoration: none;
                transition: transform .2s ease, box-shadow .2s ease;
            }

            .shop-suggested-card:hover .shop-suggested-cta {
                transform: translateY(-1px);
                box-shadow: 0 14px 28px color-mix(in srgb, var(--shop-primary) 24%, transparent);
            }

            @media (max-width: 640px) {
                .shop-suggested-grid {
                    grid-template-columns: 1fr;
                }
                .shop-suggested-header {
                    flex-direction: column;
                    align-items: flex-start;
                }

                /* Center the whole suggested section on small screens */
                .shop-suggested-section {
                    text-align: center;
                }

                .shop-suggested-header {
                    align-items: center;
                    text-align: center;
                }

                .shop-suggested-header h2 {
                    justify-content: center;
                }

                .shop-suggested-viewall {
                    justify-content: center;
                    margin: 6px auto 0;
                }

                /* Center card content on small screens */
                .shop-suggested-content {
                    text-align: center;
                }

                .shop-suggested-date {
                    justify-content: center;
                }

                .shop-suggested-actions {
                    justify-content: center;
                }
            }

            .shop-toast {
                position: fixed;
                bottom: 30px;
                right: 30px;
                background: #1f2937;
                color: white;
                padding: 12px 18px;
                border-radius: 50px;
                font-size: 14px;
                z-index: 1000;
                animation: shop-toast-in .25s ease;
                box-shadow: 0 10px 25px rgba(0,0,0,0.10);
                font-weight: 800;
            }
            @keyframes shop-toast-in {
                from { transform: translateX(100px); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes shop-toast-out {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100px); opacity: 0; }
            }

            @media (max-width: 968px) {
                .shop-event-details-grid { grid-template-columns: 1fr; }
                .shop-event-title { font-size: 1.7rem; }
                .shop-event-details-list { grid-template-columns: 1fr; }
                .shop-event-image { height: 280px; }

                /* Match home carousel behavior on smaller screens. */
                .shop-event-slider [data-shop-image-carousel-track] img {
                    aspect-ratio: 16 / 10 !important;
                    max-height: 360px !important;
                    height: auto !important;
                    width: 100% !important;
                    object-fit: cover !important;
                }
            }

            @media (max-width: 768px) {
                .shop-event-image { height: 250px; }
                .shop-event-content { padding: 20px; }

                .shop-event-slider [data-shop-image-carousel-track] img {
                    max-height: 280px !important;
                    aspect-ratio: 16 / 10 !important;
                }

                .shop-event-slider [data-shop-image-carousel-track] {
                    scroll-snap-type: x mandatory;
                }

                /* Hide carousel arrows on small screens (event details only). */
                .shop-event-slider [data-carousel-prev],
                .shop-event-slider [data-carousel-next] {
                    display: none !important;
                }
            }
        </style>
    @endpushOnce

    <div class="shop-event-details-page">
        <div class="shop-event-details-container">
            @once
                @include('shop::events.partials.event-subscribe-dialog')
            @endonce

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
                    <li>
                        <a
                            href="{{ route('shop.events.index') }}"
                            class="font-medium text-[color:var(--shop-accent)] transition hover:text-[color:var(--shop-accent-hover)] hover:underline"
                        >
                            {{ __('shop::app.events.index.title') }}
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
                        <span class="line-clamp-2">{{ $event->title }}</span>
                    </li>
                </ol>
            </nav>

            <div class="shop-event-details-grid">
                <section class="shop-event-info">
                    <div class="shop-event-slider">
                        @if ($eventImageUrls->isNotEmpty())
                            @php
                                $carouselOptions = [
                                    'autoplay_interval' => 6000,
                                    'images' => $eventImageUrls
                                        ->map(fn ($u) => ['image' => $u, 'link' => '', 'title' => $event->title])
                                        ->values()
                                        ->all(),
                                ];
                            @endphp

                            <div class="relative overflow-hidden">
                                <x-shop::carousel
                                    :options="$carouselOptions"
                                    :aria-label="$event->title"
                                />

                                @if ($categories->isNotEmpty())
                                    <div class="shop-event-category-badges" aria-label="Event categories">
                                        @foreach ($categories as $cat)
                                            @php $catName = trim((string) ($cat->name ?? '')); @endphp
                                            @if ($catName !== '')
                                                <div class="shop-event-category-pill">
                                                    <span>{{ $catName }}</span>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="shop-event-image">
                                <div class="h-full w-full bg-[color:var(--shop-surface)] flex items-center justify-center text-[color:var(--shop-placeholder)]">
                                    <span class="text-sm font-semibold">{{ __('shop::app.events.card.no-image') }}</span>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="shop-event-content">
                        <h1 class="shop-event-title">{{ $event->title }}</h1>

                        <div class="shop-event-stats">
                            @if ($eventDate !== '')
                                <div class="shop-event-stat">
                                    <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                                    <span>{{ $eventDate }}</span>
                                </div>
                            @endif

                            @if ($eventEndDate !== '' && $eventEndDate !== $eventDate)
                                <div class="shop-event-stat">
                                    <i class="fas fa-clock" aria-hidden="true"></i>
                                    <span>{{ __('shop::app.events.show.until', ['date' => $eventEndDate]) }}</span>
                                </div>
                            @endif

                            @if ($eventLocation !== '')
                                <div class="shop-event-stat">
                                    <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                                    <span>{{ $eventLocation }}</span>
                                </div>
                            @endif

                            <div class="shop-event-price-tag">
                                @if ($remainingSeats !== null)
                                    {{ __('shop::app.events.show.seats-remaining-short', ['count' => $remainingSeats]) }}
                                @else
                                    {{ __('shop::app.events.seats.unlimited') }}
                                @endif
                            </div>
                        </div>

                        <div class="shop-event-cta">
                            <div class="shop-event-cta-top">
                                <div>
                                    <div class="shop-event-cta-meta">
                                        <div class="shop-event-cta-number">
                                            {{ $remainingSeats !== null ? $remainingSeats : '∞' }}
                                        </div>
                                        <div class="shop-event-cta-text">
                                            {{ $remainingSeats !== null ? __('shop::app.events.show.seats-remaining-short', ['count' => $remainingSeats]) : __('shop::app.events.seats.unlimited') }}
                                        </div>
                                    </div>
                                    <div class="shop-event-cta-sub">
                                        @include('shop::events.partials.seats-label', ['event' => $event])
                                    </div>
                                </div>

                                @if ($isSubscribedToCurrentEvent)
                                    <span class="shop-event-cta-status" role="status">
                                        {{ __('shop::app.events.card.subscribe-registered') }}
                                    </span>
                                @else
                                    <button
                                        type="button"
                                        class="shop-event-cta-action"
                                        data-shop-event-subscribe
                                        data-student-auth="{{ auth('student')->check() ? '1' : '0' }}"
                                        data-student-login-url="{{ e($studentLoginUrl) }}"
                                        data-subscribe-url="{{ e($subscribePostUrl) }}"
                                        data-event-title="{{ e($event->title) }}"
                                        data-event-url="{{ e($detailUrl) }}"
                                        @if (! $canSubscribe) disabled aria-disabled="true" @endif
                                    >
                                        {{ $canSubscribe ? __('shop::app.events.card.subscribe') : __('shop::app.events.card.subscribe-unavailable') }}
                                    </button>
                                @endif
                            </div>

                            @if ($progressPct !== null)
                                <div class="shop-event-cta-bar" aria-hidden="true">
                                    <div class="shop-event-cta-fill" style="width: {{ $progressPct }}%"></div>
                                </div>
                            @endif
                        </div>

                        <div class="shop-event-description">
                            <div class="shop-event-section-title">
                                <i class="fas fa-info-circle" aria-hidden="true"></i>
                                <span>{{ __('shop::app.events.show.details') }}</span>
                            </div>

                            @if ($event->description)
                                {!! $event->description !!}
                            @else
                                <div class="text-gray-500 font-semibold">{{ __('shop::app.events.show.no-description') }}</div>
                            @endif
                        </div>

                        @if ($detailFields->isNotEmpty())
                            <div class="shop-event-details-list">
                                @foreach ($detailFields as $field)
                                    @php
                                        $fieldName = trim((string) ($field->name ?? ''));
                                        $fieldValue = is_string($field->value) ? trim($field->value) : $field->value;
                                        $icon = 'fas fa-info-circle';
                                        $nameLower = function_exists('mb_strtolower')
                                            ? mb_strtolower($fieldName, 'UTF-8')
                                            : strtolower($fieldName);
                                        if (str_contains($nameLower, 'مشارك') || str_contains($nameLower, 'participants') || str_contains($nameLower, 'عدد')) {
                                            $icon = 'fas fa-users';
                                        } elseif (str_contains($nameLower, 'جائزة') || str_contains($nameLower, 'trophy') || str_contains($nameLower, 'award')) {
                                            $icon = 'fas fa-trophy';
                                        } elseif (str_contains($nameLower, 'لغة') || str_contains($nameLower, 'language')) {
                                            $icon = 'fas fa-language';
                                        } elseif (str_contains($nameLower, 'شهادة') || str_contains($nameLower, 'certificate')) {
                                            $icon = 'fas fa-certificate';
                                        }
                                    @endphp

                                    <div class="shop-event-detail-item">
                                        <div class="shop-event-detail-icon">
                                            <i class="{{ $icon }}" aria-hidden="true"></i>
                                        </div>
                                        <div class="shop-event-detail-info">
                                            <h4>{{ $fieldName }}</h4>
                                            <p>
                                                @if ($field->type === 'image' && $fieldValue)
                                                    <img
                                                        src="{{ Storage::url($fieldValue) }}"
                                                        alt="{{ $fieldName }}"
                                                        class="mt-2 max-h-56 rounded-lg object-contain"
                                                    >
                                                @else
                                                    {{ $fieldValue }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if ($eventOrganizer !== '' || $eventLocation !== '')
                            <div class="shop-event-organizer">
                                <div class="shop-event-organizer-avatar">
                                    <i class="fas fa-user-tie" aria-hidden="true"></i>
                                </div>
                                <div class="shop-event-organizer-info">
                                    <h4>{{ $eventOrganizer !== '' ? $eventOrganizer : __('shop::app.events.show.organizer-fallback') }}</h4>
                                    <p>
                                        @if ($eventLocation !== '')
                                            {{ __('shop::app.events.show.organizer-line', ['location' => $eventLocation]) }}
                                        @else
                                            {{ __('shop::app.events.show.organizer-line-fallback') }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </section>
            </div>

            @if (isset($suggestedEvents) && $suggestedEvents->isNotEmpty())
                <section class="shop-suggested-section">
                    <div class="shop-suggested-header">
                        <div>
                            <h2>{{ __('shop::app.events.show.suggestions-title') }}</h2>
                            <div class="shop-suggested-subtitle">
                                {{ __('shop::app.events.show.suggestions-subtitle') }}
                            </div>
                        </div>
                        <a
                            href="{{ route('shop.events.index') }}"
                            class="shop-suggested-viewall"
                        >
                            {{ __('shop::app.events.show.view-all') }}
                        </a>
                    </div>

                    <ul class="shop-suggested-grid">
                        @foreach ($suggestedEvents as $sEvent)
                            <li class="min-w-0">
                                @include('shop::events.partials.event-card', [
                                    'event' => $sEvent,
                                    'variant' => 'grid',
                                    'showExcerpt' => true,
                                    'subscribedEventIds' => [],
                                ])
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </div>
</x-shop::layouts>

@push('scripts')
    <script>
        (function () {
            // Event details uses the same carousel component as home page (x-shop::carousel),
            // so no custom JS is needed here.
        })();
    </script>
@endpush
