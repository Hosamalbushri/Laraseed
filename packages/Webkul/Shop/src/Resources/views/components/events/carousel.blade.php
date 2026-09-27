@props([
    'options' => [],
    'events',
    'subscribedEventIds' => [],
    'ariaLabel' => null,
])

@php
    $title = isset($options['title']) ? __($options['title']) : __('shop::app.home.event-carousel.title');
    $isRtl = in_array(app()->getLocale(), ['ar', 'fa'], true);
@endphp

@pushOnce('styles', 'shop-home-events-carousel-colors')
    <style>
        .shop-home-events-section {
            position: relative;
            overflow: hidden;
            border-bottom: 1px solid color-mix(in srgb, var(--shop-primary) 16%, white);
            background:
                radial-gradient(1200px 420px at 20% -10%, color-mix(in srgb, var(--shop-primary) 18%, transparent), transparent 60%),
                radial-gradient(900px 360px at 90% 10%, color-mix(in srgb, var(--shop-accent) 16%, transparent), transparent 55%),
                linear-gradient(180deg, #fff 0%, #f8faff 100%);
            padding: 2.5rem 0 3rem;
        }

        .shop-home-events-grid-overlay {
            position: absolute;
            inset: 0;
            opacity: 0.3;
            pointer-events: none;
            background-image:
                linear-gradient(color-mix(in srgb, var(--shop-primary) 8%, transparent) 1px, transparent 1px),
                linear-gradient(90deg, color-mix(in srgb, var(--shop-primary) 8%, transparent) 1px, transparent 1px);
            background-size: 30px 30px;
        }

        .shop-home-events-glow-primary,
        .shop-home-events-glow-accent {
            position: absolute;
            width: 16rem;
            height: 16rem;
            border-radius: 999px;
            pointer-events: none;
            filter: blur(48px);
        }

        .shop-home-events-glow-primary {
            top: -6rem;
            inset-inline-end: -5rem;
            background: color-mix(in srgb, var(--shop-primary) 25%, transparent);
        }

        .shop-home-events-glow-accent {
            bottom: -6rem;
            inset-inline-start: -5rem;
            background: color-mix(in srgb, var(--shop-accent) 20%, transparent);
        }

        .shop-home-events-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, var(--shop-primary) 22%, white);
            background: #fff;
            padding: 0.375rem 0.875rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--shop-primary);
            box-shadow: 0 4px 14px color-mix(in srgb, var(--shop-primary) 10%, transparent);
        }

        .shop-home-events-badge-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 999px;
            background: var(--shop-primary);
            color: #fff;
            font-size: 10px;
        }

        .shop-home-events-view-all {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border-radius: 0.75rem;
            border: 1px solid color-mix(in srgb, var(--shop-primary) 22%, white);
            background: #fff;
            padding: 0.625rem 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--shop-primary);
            box-shadow: 0 4px 14px color-mix(in srgb, var(--shop-primary) 10%, transparent);
            transition: all .2s ease;
        }

        .shop-home-events-view-all:hover {
            background: color-mix(in srgb, var(--shop-primary) 8%, #fff);
            box-shadow: 0 8px 18px color-mix(in srgb, var(--shop-primary) 14%, transparent);
        }

        .shop-home-events-empty {
            border: 1px dashed color-mix(in srgb, var(--shop-primary) 24%, white);
            background: color-mix(in srgb, var(--shop-badge-color) 8%, #fff);
            color: #475569;
        }
    </style>
@endPushOnce

<section
    class="shop-home-events-section"
    @if ($ariaLabel) aria-label="{{ $ariaLabel }}" @endif
>
    <div class="shop-home-events-grid-overlay" aria-hidden="true"></div>
    <div class="shop-home-events-glow-primary" aria-hidden="true"></div>
    <div class="shop-home-events-glow-accent" aria-hidden="true"></div>

    <div class="relative z-10 container px-4 max-md:px-4 lg:px-[60px]">
        <div class="mb-8 flex flex-col items-center gap-4 text-center sm:flex-row sm:items-end sm:justify-between sm:text-start">
            <div class="space-y-3">
                <span class="shop-home-events-badge">
                    <span class="shop-home-events-badge-icon">
                        <i class="fas fa-calendar-check text-[10px]" aria-hidden="true"></i>
                    </span>
                    {{ __('shop::app.home.event-carousel.title') }}
                </span>

                <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                    {{ $title }}
                </h2>

                <p class="max-w-2xl text-sm leading-relaxed text-slate-600">
                    {{ __('shop::app.events.index.subheading') }}
                </p>
            </div>

            <a
                href="{{ route('shop.events.index') }}"
                class="group shop-home-events-view-all"
            >
                {{ __('shop::app.home.event-carousel.view-all') }}
            </a>
        </div>

        @if ($events->isEmpty())
            <p class="shop-home-events-empty rounded-2xl px-6 py-12 text-center backdrop-blur-sm">
                {{ __('shop::app.home.event-carousel.empty') }}
            </p>
        @else
            <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 lg:gap-8">
                @foreach ($events as $event)
                    <li class="min-w-0">
                        @include('shop::events.partials.event-card', [
                            'event' => $event,
                            'variant' => 'grid',
                            'showExcerpt' => true,
                            'subscribedEventIds' => $subscribedEventIds,
                        ])
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
