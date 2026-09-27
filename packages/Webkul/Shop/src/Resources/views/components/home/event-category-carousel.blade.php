@props([
    'options' => [],
    'categories',
    'ariaLabel' => null,
])

@php
    $title = isset($options['title']) && $options['title'] !== ''
        ? __($options['title'])
        : __('shop::app.home.category-carousel.title');
    $isRtl = in_array(app()->getLocale(), ['ar', 'fa'], true);
    $allCategoryLabel = $isRtl ? 'الكل' : 'All';
    $categoryIcons = [
        'fa-laptop-code',
        'fa-chalkboard-user',
        'fa-microphone-alt',
        'fa-users',
        'fa-futbol',
        'fa-palette',
        'fa-flask',
        'fa-hand-holding-heart',
    ];
@endphp

@pushOnce('styles', 'shop-event-category-carousel-premium')
    <style>
        .shop-event-categories-section {
            background: #f5f7fa;
            padding: 40px 0 46px;
        }
        .shop-event-categories-carousel {
            position: relative;
        }
        .shop-event-categories-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            gap: 12px;
        }
        .shop-event-categories-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .shop-event-categories-title i {
            font-size: 26px;
            color: var(--shop-icon-color);
        }
        .shop-event-categories-title h2 {
            font-size: 1.7rem;
            font-weight: 900;
            color: #1f2937;
        }
        .shop-event-categories-nav {
            display: flex;
            gap: 10px;
        }
        .shop-event-categories-btn {
            width: 44px;
            height: 44px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            cursor: pointer;
            transition: all .25s ease;
        }
        .shop-event-categories-btn:hover {
            background: var(--shop-primary);
            color: #fff;
            border-color: transparent;
            transform: scale(1.04);
        }
        .shop-event-categories-wrapper {
            overflow: hidden;
            border-radius: 28px;
        }
        .shop-event-categories-track {
            display: flex;
            gap: 24px;
            transition: transform .45s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            will-change: transform;
        }
        .shop-event-category-card {
            flex: 0 0 calc(25% - 18px);
            min-width: calc(25% - 18px);
            background: #fff;
            border-radius: 24px;
            padding: 28px 20px;
            text-align: center;
            border: 1px solid #e5e7eb;
            position: relative;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            transition: all .35s ease;
        }
        .shop-event-category-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: var(--shop-primary);
            opacity: 0;
            transition: opacity .35s ease;
            z-index: 0;
        }
        .shop-event-category-card:hover::before {
            opacity: 0.06;
        }
        .shop-event-category-card:hover {
            border-color: color-mix(in srgb, var(--shop-primary) 70%, white);
            box-shadow: 0 20px 35px -12px color-mix(in srgb, var(--shop-primary) 36%, transparent);
        }
        .shop-event-category-icon {
            width: 80px;
            height: 80px;
            background: color-mix(in srgb, var(--shop-badge-color) 14%, #fff);
            border-radius: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 34px;
            color: var(--shop-icon-color);
            transition: all .3s ease;
            position: relative;
            z-index: 1;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        .shop-event-category-card:hover .shop-event-category-icon {
            background: var(--shop-icon-color);
            color: #fff;
        }
        .shop-event-category-name {
            font-size: 1.1rem;
            font-weight: 800;
            color: #1f2937;
            position: relative;
            z-index: 1;
            line-height: 1.35;
        }
        .shop-event-categories-dots {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 28px;
        }
        .shop-event-categories-dot {
            width: 10px;
            height: 10px;
            background: #d1d5db;
            border-radius: 999px;
            border: 0;
            cursor: pointer;
            transition: all .25s ease;
        }
        .shop-event-categories-dot[data-active="true"] {
            width: 28px;
            background: var(--shop-primary);
        }
        @media (max-width: 1200px) {
            .shop-event-category-card {
                flex: 0 0 calc(33.33% - 16px);
                min-width: calc(33.33% - 16px);
            }
        }
        @media (max-width: 900px) {
            .shop-event-category-card {
                flex: 0 0 calc(50% - 12px);
                min-width: calc(50% - 12px);
            }
        }
        @media (max-width: 600px) {
            /* فقط عند وجود شريط البطاقات: العنوان → البطاقات → الأسهم → النقاط */
            .shop-event-categories-carousel:has(.shop-event-categories-wrapper) {
                display: flex;
                flex-direction: column;
            }
            .shop-event-categories-carousel:has(.shop-event-categories-wrapper) .shop-event-categories-header {
                display: contents;
            }
            .shop-event-categories-carousel:has(.shop-event-categories-wrapper) .shop-event-categories-title {
                order: 1;
                justify-content: center;
                flex-wrap: wrap;
                text-align: center;
                margin-bottom: 20px;
            }
            .shop-event-categories-carousel:has(.shop-event-categories-wrapper) .shop-event-categories-wrapper {
                order: 2;
            }
            .shop-event-categories-carousel:has(.shop-event-categories-wrapper) .shop-event-categories-nav {
                order: 3;
                justify-content: center;
                width: 100%;
                margin-top: 4px;
            }
            .shop-event-categories-carousel:has(.shop-event-categories-wrapper) .shop-event-categories-dots {
                order: 4;
                margin-top: 18px;
            }
            .shop-event-categories-header {
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-align: center;
                gap: 14px;
            }
            .shop-event-categories-title {
                justify-content: center;
                flex-wrap: wrap;
            }
            .shop-event-categories-nav {
                justify-content: center;
            }
            .shop-event-categories-title h2 {
                font-size: 1.3rem;
            }
            .shop-event-category-card {
                flex: 0 0 100%;
                min-width: 100%;
                padding: 22px 18px;
            }
            .shop-event-category-icon {
                width: 72px;
                height: 72px;
                border-radius: 22px;
                font-size: 30px;
            }
        }
    </style>
@endPushOnce

<section
    class="shop-event-categories-section"
    @if ($ariaLabel) aria-label="{{ $ariaLabel }}" @endif
>
    <div class="container px-4 max-md:px-4 lg:px-[60px]">
        <div class="shop-event-categories-carousel" data-event-category-carousel>
            <div class="shop-event-categories-header">
                <div class="shop-event-categories-title">
                    <i class="fas fa-tags" aria-hidden="true"></i>
                    <h2>{{ $title }}</h2>
                </div>

                <div class="shop-event-categories-nav">
                    <button
                        type="button"
                        class="shop-event-categories-btn"
                        data-category-prev
                        aria-label="{{ __('shop::app.home.carousel.prev') }}"
                    >
                        <i class="fas {{ $isRtl ? 'fa-chevron-right' : 'fa-chevron-left' }}" aria-hidden="true"></i>
                    </button>
                    <button
                        type="button"
                        class="shop-event-categories-btn"
                        data-category-next
                        aria-label="{{ __('shop::app.home.carousel.next') }}"
                    >
                        <i class="fas {{ $isRtl ? 'fa-chevron-left' : 'fa-chevron-right' }}" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="shop-event-categories-wrapper">
                <div class="shop-event-categories-track" data-category-track>
                    <a
                        href="{{ route('shop.events.index') }}"
                        class="shop-event-category-card"
                        aria-label="{{ $allCategoryLabel }}"
                    >
                        <span class="shop-event-category-icon">
                            <i class="fas fa-layer-group" aria-hidden="true"></i>
                        </span>

                        <h3 class="shop-event-category-name">
                            {{ $allCategoryLabel }}
                        </h3>
                    </a>

                    @foreach ($categories as $index => $category)
                        @php
                            $name = trim((string) $category->name);
                            $href = route('shop.events.index', ['category' => $category->id]);
                            $iconClass = $categoryIcons[$index % count($categoryIcons)];
                        @endphp

                        <a
                            href="{{ $href }}"
                            class="shop-event-category-card"
                            aria-label="{{ $category->name }}"
                        >
                            <span class="shop-event-category-icon">
                                <i class="fas {{ $iconClass }}" aria-hidden="true"></i>
                            </span>

                            <h3 class="shop-event-category-name">
                                {{ $name !== '' ? $name : '—' }}
                            </h3>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="shop-event-categories-dots" data-category-dots></div>
        </div>
    </div>
</section>

@pushOnce('scripts', 'shop-event-category-carousel-script')
    <script>
        (function () {
            function itemsPerView() {
                if (window.innerWidth <= 600) return 1;
                if (window.innerWidth <= 900) return 2;
                if (window.innerWidth <= 1200) return 3;
                return 4;
            }

            function initCarousel(root) {
                const track = root.querySelector('[data-category-track]');
                if (!track) return;

                const cards = Array.from(track.children);
                if (!cards.length) return;

                const prev = root.querySelector('[data-category-prev]');
                const next = root.querySelector('[data-category-next]');
                const dotsWrap = root.querySelector('[data-category-dots]');
                const direction = @json($isRtl ? 1 : -1);

                let perView = itemsPerView();
                let page = 0;

                function totalPages() {
                    return Math.max(1, Math.ceil(cards.length / perView));
                }

                function cardStep() {
                    if (!cards[0]) return 0;
                    const style = window.getComputedStyle(track);
                    const gap = parseFloat(style.columnGap || style.gap || '24') || 24;
                    return cards[0].offsetWidth + gap;
                }

                function buildDots() {
                    if (!dotsWrap) return;
                    dotsWrap.innerHTML = '';
                    for (let i = 0; i < totalPages(); i++) {
                        const dot = document.createElement('button');
                        dot.type = 'button';
                        dot.className = 'shop-event-categories-dot';
                        dot.setAttribute('data-active', i === page ? 'true' : 'false');
                        dot.setAttribute('aria-label', 'go-to-' + (i + 1));
                        dot.addEventListener('click', () => go(i));
                        dotsWrap.appendChild(dot);
                    }
                }

                function syncDots() {
                    if (!dotsWrap) return;
                    dotsWrap.querySelectorAll('.shop-event-categories-dot').forEach((dot, i) => {
                        dot.setAttribute('data-active', i === page ? 'true' : 'false');
                    });
                }

                function go(nextPage) {
                    const max = totalPages() - 1;
                    page = Math.max(0, Math.min(nextPage, max));
                    const offset = page * cardStep() * perView;
                    track.style.transform = 'translateX(' + (direction * offset) + 'px)';
                    syncDots();
                }

                if (prev) prev.addEventListener('click', () => go(page - 1));
                if (next) next.addEventListener('click', () => go(page + 1));

                let rt;
                window.addEventListener('resize', function () {
                    window.clearTimeout(rt);
                    rt = window.setTimeout(function () {
                        perView = itemsPerView();
                        page = 0;
                        buildDots();
                        go(0);
                    }, 180);
                });

                buildDots();
                go(0);
            }

            document.querySelectorAll('[data-event-category-carousel]').forEach(initCarousel);
        })();
    </script>
@endPushOnce
