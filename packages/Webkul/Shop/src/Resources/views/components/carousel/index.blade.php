@props([
    'options' => [],
    'ariaLabel' => null,
])

@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $raw = $options['images'] ?? [];
    $images = [];
    foreach ($raw as $item) {
        if (is_string($item) && $item !== '') {
            $images[] = ['image' => $item, 'link' => '', 'title' => ''];
        } elseif (is_array($item) && ! empty($item['image'])) {
            $images[] = [
                'image' => $item['image'],
                'link'  => $item['link'] ?? '',
                'title' => $item['title'] ?? '',
            ];
        }
    }

    $autoplayMs = (int) ($options['autoplay_interval'] ?? 5500);
    if ($autoplayMs < 2500) {
        $autoplayMs = 5500;
    }
    if ($autoplayMs > 60000) {
        $autoplayMs = 60000;
    }

    $resolveCarouselSrc = function (string $rawPath): string {
        if (Str::startsWith($rawPath, ['http://', 'https://', '//'])) {
            return $rawPath;
        }
        $clean = ltrim(str_replace('storage/', '', $rawPath), '/');

        return Storage::disk('public')->exists($clean) ? Storage::url($clean) : asset('storage/'.$clean);
    };

    $isRtl = in_array(app()->getLocale(), ['ar', 'fa'], true);
    $prevArrowPath = $isRtl ? 'm9 18 6-6-6-6' : 'm15 18-6-6 6-6';
    $nextArrowPath = $isRtl ? 'm15 18-6-6 6-6' : 'm9 18 6-6-6-6';
@endphp

@if (count($images) > 0)
    <div
        class="w-full"
        data-shop-image-carousel
        role="region"
        @if ($ariaLabel) aria-label="{{ $ariaLabel }}" @endif
        aria-roledescription="carousel"
    >
        <div class="relative w-full">
            <div
                class="scrollbar-hide flex w-full snap-x snap-mandatory overflow-x-auto scroll-smooth"
                tabindex="0"
                data-shop-image-carousel-track
                data-interval="{{ $autoplayMs }}"
            >
                @foreach ($images as $index => $image)
                    <div
                        class="relative min-w-full shrink-0 snap-center snap-always"
                        data-carousel-slide
                        role="group"
                        aria-roledescription="slide"
                        aria-label="{{ __('shop::app.home.carousel.slide-label', ['current' => $index + 1, 'total' => count($images)]) }}"
                    >
                        @if (! empty($image['link']))
                            <a
                                href="{{ $image['link'] }}"
                                class="block"
                            >
                        @endif

                        <img
                            src="{{ $resolveCarouselSrc((string) $image['image']) }}"
                            alt="{{ $image['title'] ?: __('shop::app.home.carousel.slide-alt', ['n' => $index + 1]) }}"
                            class="aspect-[2.743/1] max-h-[min(100vh,560px)] w-full object-cover"
                            @if ($index === 0) fetchpriority="high" @endif
                            loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                            draggable="false"
                        >

                        @if (! empty($image['link']))
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>

            @if (count($images) > 1)
                <div class="pointer-events-none absolute inset-0 flex items-center justify-between gap-2 px-2 max-sm:px-1">
                    <button
                        type="button"
                        class="pointer-events-auto inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-[color:var(--shop-border-soft)] bg-white/95 text-[color:var(--shop-primary)] shadow-md backdrop-blur-sm transition hover:bg-[color:var(--shop-surface)] hover:shadow-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--shop-ring)] focus-visible:ring-offset-2 max-sm:h-10 max-sm:w-10"
                        data-carousel-prev
                        aria-label="{{ __('shop::app.home.carousel.prev') }}"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="h-6 w-6"
                            aria-hidden="true"
                        >
                            <path d="{{ $prevArrowPath }}" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        class="pointer-events-auto inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-[color:var(--shop-border-soft)] bg-white/95 text-[color:var(--shop-primary)] shadow-md backdrop-blur-sm transition hover:bg-[color:var(--shop-surface)] hover:shadow-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--shop-ring)] focus-visible:ring-offset-2 max-sm:h-10 max-sm:w-10"
                        data-carousel-next
                        aria-label="{{ __('shop::app.home.carousel.next') }}"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="h-6 w-6"
                            aria-hidden="true"
                        >
                            <path d="{{ $nextArrowPath }}" />
                        </svg>
                    </button>
                </div>
            @endif
        </div>

        @if (count($images) > 1)
            <nav
                class="flex items-center justify-center gap-2.5 px-2 pt-3 pb-1"
                data-carousel-dots
                aria-label="{{ __('shop::app.home.carousel.dots-label') }}"
            >
                @foreach ($images as $index => $_image)
                    <button
                        type="button"
                        data-carousel-dot
                        data-carousel-dot-index="{{ $index }}"
                        data-active="{{ $index === 0 ? 'true' : 'false' }}"
                        aria-label="{{ __('shop::app.home.carousel.dot-go-to', ['n' => $index + 1]) }}"
                        aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                        class="shop-carousel-dot h-2 min-h-[8px] min-w-[8px] rounded-full border border-transparent bg-[color:var(--shop-border-soft)] transition-[width,background-color,border-color,opacity] duration-200 ease-out hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--shop-ring)] focus-visible:ring-offset-2 data-[active=true]:w-7 data-[active=true]:border-[color:var(--shop-primary)] data-[active=true]:bg-[color:var(--shop-primary)] data-[active=false]:w-2 data-[active=false]:opacity-55"
                    ></button>
                @endforeach
            </nav>
        @endif
    </div>

    @if (count($images) > 1)
        @pushOnce('scripts', 'shop-image-carousel-autoplay')
            <script>
                (function () {
                    function initCarouselTrack(track) {
                        var slides = track.querySelectorAll('[data-carousel-slide]');
                        if (!slides.length || slides.length < 2) {
                            return;
                        }

                        var region = track.closest('[data-shop-image-carousel]');
                        if (!region) {
                            region = track.parentElement;
                        }

                        var reduceMotion = false;
                        try {
                            reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                        } catch (e) {}

                        var intervalMs = parseInt(track.getAttribute('data-interval') || '5500', 10);
                        if (isNaN(intervalMs) || intervalMs < 2500) {
                            intervalMs = 5500;
                        }

                        var index = 0;
                        var timer = null;
                        var paused = false;
                        var dots = region.querySelectorAll('[data-carousel-dot]');
                        var dotsRaf = null;

                        function updateDots(i) {
                            if (!dots || !dots.length) {
                                return;
                            }
                            var n = Math.max(0, Math.min(i, dots.length - 1));
                            for (var j = 0; j < dots.length; j++) {
                                var on = j === n;
                                dots[j].setAttribute('data-active', on ? 'true' : 'false');
                                dots[j].setAttribute('aria-current', on ? 'true' : 'false');
                            }
                        }

                        function nearestSlideIndex() {
                            var rect = track.getBoundingClientRect();
                            var center = rect.left + rect.width / 2;
                            var best = 0;
                            var bestDist = Infinity;
                            for (var i = 0; i < slides.length; i++) {
                                var r = slides[i].getBoundingClientRect();
                                var c = r.left + r.width / 2;
                                var d = Math.abs(c - center);
                                if (d < bestDist) {
                                    bestDist = d;
                                    best = i;
                                }
                            }
                            return best;
                        }

                        function goTo(i) {
                            var el = slides[i];
                            if (!el || typeof el.scrollIntoView !== 'function') {
                                return;
                            }
                            var behavior = reduceMotion ? 'auto' : 'smooth';
                            el.scrollIntoView({
                                behavior: behavior,
                                block: 'nearest',
                                inline: 'start',
                            });
                            updateDots(i);
                        }

                        function tick() {
                            index = (index + 1) % slides.length;
                            goTo(index);
                        }

                        function start() {
                            if (reduceMotion || paused) {
                                return;
                            }
                            stopTimer();
                            timer = window.setInterval(tick, intervalMs);
                        }

                        function stopTimer() {
                            if (timer !== null) {
                                window.clearInterval(timer);
                                timer = null;
                            }
                        }

                        function pause() {
                            paused = true;
                            stopTimer();
                        }

                        function resume() {
                            paused = false;
                            start();
                        }

                        function restartAutoplayIfNeeded() {
                            stopTimer();
                            if (!reduceMotion && !paused) {
                                start();
                            }
                        }

                        function step(delta) {
                            index = nearestSlideIndex();
                            index = (index + delta + slides.length) % slides.length;
                            goTo(index);
                            restartAutoplayIfNeeded();
                        }

                        var prevBtn = region.querySelector('[data-carousel-prev]');
                        var nextBtn = region.querySelector('[data-carousel-next]');

                        if (prevBtn) {
                            prevBtn.addEventListener('click', function (e) {
                                e.preventDefault();
                                e.stopPropagation();
                                step(-1);
                            });
                        }

                        if (nextBtn) {
                            nextBtn.addEventListener('click', function (e) {
                                e.preventDefault();
                                e.stopPropagation();
                                step(1);
                            });
                        }

                        dots.forEach(function (dot) {
                            dot.addEventListener('click', function (e) {
                                e.preventDefault();
                                var j = parseInt(dot.getAttribute('data-carousel-dot-index') || '-1', 10);
                                if (isNaN(j) || j < 0 || j >= slides.length) {
                                    return;
                                }
                                index = j;
                                goTo(index);
                                restartAutoplayIfNeeded();
                            });
                        });

                        region.addEventListener('keydown', function (e) {
                            if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') {
                                return;
                            }
                            if (!region.contains(e.target)) {
                                return;
                            }
                            var rtl = false;
                            try {
                                rtl = window.getComputedStyle(track).direction === 'rtl';
                            } catch (err) {}
                            var prevKey = rtl ? 'ArrowRight' : 'ArrowLeft';
                            var nextKey = rtl ? 'ArrowLeft' : 'ArrowRight';
                            if (e.key === prevKey) {
                                e.preventDefault();
                                step(-1);
                            } else if (e.key === nextKey) {
                                e.preventDefault();
                                step(1);
                            }
                        });

                        track.addEventListener('scroll', function () {
                            if (dotsRaf !== null) {
                                window.cancelAnimationFrame(dotsRaf);
                            }
                            dotsRaf = window.requestAnimationFrame(function () {
                                dotsRaf = null;
                                updateDots(nearestSlideIndex());
                            });
                            stopTimer();
                            window.clearTimeout(track._carouselScrollEndT);
                            track._carouselScrollEndT = window.setTimeout(function () {
                                index = nearestSlideIndex();
                                updateDots(index);
                                if (!paused) {
                                    start();
                                }
                            }, 180);
                        }, { passive: true });

                        region.addEventListener('mouseenter', pause);
                        region.addEventListener('mouseleave', resume);
                        region.addEventListener('focusin', pause);
                        region.addEventListener('focusout', function (e) {
                            if (!region.contains(e.relatedTarget)) {
                                resume();
                            }
                        });

                        document.addEventListener('visibilitychange', function () {
                            if (document.hidden) {
                                stopTimer();
                            } else if (!paused) {
                                start();
                            }
                        });

                        updateDots(0);

                        if (!reduceMotion) {
                            start();
                        }
                    }

                    function boot() {
                        document.querySelectorAll('[data-shop-image-carousel-track]').forEach(initCarouselTrack);
                    }

                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', boot);
                    } else {
                        boot();
                    }
                })();
            </script>
        @endPushOnce
    @endif
@endif
