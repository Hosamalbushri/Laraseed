@props([
    'options' => [],
    'ariaLabel' => null,
])

@php
    use Illuminate\Support\Str;

    $defaults = [
        'effects' => [
            'particles' => true,
            'orbs' => true,
            'grid' => true,
            'custom_cursor' => false,
            'visual_cards' => true,
            'scroll_hint' => true,
            'font_awesome' => true,
        ],
        'particles_count' => 80,
        'colors' => [
            'bg_start' => '#0a0a2a',
            'bg_mid' => '#1a1a3a',
            'bg_end' => '#0f0f2a',
            'accent' => '#8b5cf6',
            'accent_2' => '#6366f1',
            'text' => '#ffffff',
            'text_muted' => 'rgba(255,255,255,0.7)',
            'orb_1' => 'rgba(139, 92, 246, 0.8)',
            'orb_2' => 'rgba(236, 72, 153, 0.6)',
            'orb_3' => 'rgba(59, 130, 246, 0.7)',
        ],
        'badge' => [
            'enabled' => true,
            'icon' => 'fas fa-calendar-star',
            'text' => '',
        ],
        'heading' => [
            'line1' => '',
            'highlight' => '',
        ],
        'typing' => [
            'prefix' => '',
            'words' => [],
        ],
        'description' => '',
        'primary_cta' => [
            'label' => '',
            'url' => '',
            'icon' => 'fas fa-compass',
        ],
        'secondary_cta' => [
            'enabled' => true,
            'label' => '',
            'url' => '',
            'icon' => 'fas fa-plus-circle',
        ],
        'cards' => [
            ['icon' => 'fas fa-laptop-code', 'date_line' => '', 'title' => '', 'attendees' => ''],
            ['icon' => 'fas fa-microphone-alt', 'date_line' => '', 'title' => '', 'attendees' => ''],
            ['icon' => 'fas fa-chalkboard-user', 'date_line' => '', 'title' => '', 'attendees' => ''],
        ],
        'scroll_hint' => [
            'text' => '',
        ],
    ];

    $ih = array_replace_recursive($defaults, is_array($options) ? $options : []);
    $uid = 'ih-' . Str::random(10);
    $c = $ih['colors'];
    $fx = $ih['effects'];

    $typingWords = array_values(array_filter(array_map('trim', $ih['typing']['words'] ?? [])));
    if ($typingWords === []) {
        $typingWords = [__('shop::app.home.immersive-hero.typing-fallback')];
    }

    $cards = array_slice($ih['cards'] ?? [], 0, 3);
    while (count($cards) < 3) {
        $cards[] = ['icon' => 'fas fa-star', 'date_line' => '', 'title' => '', 'attendees' => ''];
    }

    $jsonConfig = json_encode([
        'particles' => (bool) ($fx['particles'] ?? false),
        'count' => max(20, min(200, (int) ($ih['particles_count'] ?? 80))),
        'words' => $typingWords,
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP);
@endphp

<section
    class="ih-root relative w-full overflow-x-hidden border-b border-slate-100 py-10"
    id="{{ $uid }}"
    @if ($ariaLabel) aria-label="{{ $ariaLabel }}" @endif
    style="
        --ih-bg-start: {{ $c['bg_start'] }};
        --ih-bg-mid: {{ $c['bg_mid'] }};
        --ih-bg-end: {{ $c['bg_end'] }};
        --ih-accent: {{ $c['accent'] }};
        --ih-accent-2: {{ $c['accent_2'] }};
        --ih-text: {{ $c['text'] }};
        --ih-text-muted: {{ $c['text_muted'] }};
        --ih-orb-1: {{ $c['orb_1'] }};
        --ih-orb-2: {{ $c['orb_2'] }};
        --ih-orb-3: {{ $c['orb_3'] }};
        background: linear-gradient(135deg, var(--ih-bg-start) 0%, var(--ih-bg-mid) 50%, var(--ih-bg-end) 100%);
    "
    data-immersive-hero-config="{{ base64_encode($jsonConfig) }}"
>
    @if ($fx['custom_cursor'] ?? false)
        <div class="ih-cursor pointer-events-none fixed z-[9999] hidden h-5 w-5 rounded-full border-2 border-white/50 max-[1024px]:!hidden" aria-hidden="true"></div>
        <div class="ih-cursor-follower pointer-events-none fixed z-[9998] hidden h-10 w-10 rounded-full bg-violet-500/20 blur-sm max-[1024px]:!hidden" aria-hidden="true"></div>
    @endif

    <div
        class="ih-hero pointer-events-none absolute inset-0 overflow-hidden"
        aria-hidden="true"
    >
        @if ($fx['orbs'] ?? false)
            <div class="ih-orb ih-orb-1 pointer-events-none absolute rounded-full opacity-50 blur-[80px]" aria-hidden="true"></div>
            <div class="ih-orb ih-orb-2 pointer-events-none absolute rounded-full opacity-50 blur-[80px]" aria-hidden="true"></div>
            <div class="ih-orb ih-orb-3 pointer-events-none absolute rounded-full opacity-50 blur-[80px]" aria-hidden="true"></div>
        @endif

        @if ($fx['grid'] ?? false)
            <div class="pointer-events-none absolute inset-0 opacity-100" style="background-image: linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px); background-size: 50px 50px;" aria-hidden="true"></div>
        @endif

        @if ($fx['particles'] ?? false)
            <canvas
                id="{{ $uid }}-particles"
                class="pointer-events-none absolute inset-0 h-full w-full"
                aria-hidden="true"
            ></canvas>
        @endif
    </div>

    <div class="container relative z-10 max-w-full overflow-visible px-4 sm:px-4 lg:px-[60px]">
            <div class="grid min-w-0 items-start gap-8 max-lg:gap-10 lg:grid-cols-2 lg:items-center lg:gap-16">
                <div class="ih-text min-w-0 overflow-visible max-lg:flex max-lg:flex-col max-lg:items-center max-lg:text-center">
                    @if (($ih['badge']['enabled'] ?? false) && (trim((string) ($ih['badge']['text'] ?? '')) !== ''))
                        <div class="mb-8 inline-flex max-w-full flex-wrap items-center justify-center gap-3 whitespace-normal rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium backdrop-blur-md sm:px-5" style="color: var(--ih-text);">
                            <span class="inline-block h-2 w-2 animate-pulse rounded-full bg-emerald-500"></span>
                            @if (($fx['font_awesome'] ?? false) && trim((string) ($ih['badge']['icon'] ?? '')) !== '')
                                <i class="{{ e($ih['badge']['icon']) }}" style="color: var(--ih-accent);"></i>
                            @endif
                            <span>{{ $ih['badge']['text'] }}</span>
                        </div>
                    @endif

                    <h1 class="mb-6 w-full max-w-full overflow-visible break-words text-3xl font-extrabold leading-normal sm:leading-tight sm:text-5xl lg:text-6xl" style="color: var(--ih-text);">
                        @if (trim((string) ($ih['heading']['line1'] ?? '')) !== '')
                            {!! nl2br(e($ih['heading']['line1'])) !!}<br>
                        @endif
                        @if (trim((string) ($ih['heading']['highlight'] ?? '')) !== '')
                            <span class="inline-block max-w-full break-words bg-gradient-to-br from-violet-300 via-blue-400 to-pink-400 bg-clip-text pb-1 text-transparent">{{ $ih['heading']['highlight'] }}</span>
                        @endif
                    </h1>

                    <div
                        class="mb-5 flex w-full max-w-full flex-wrap items-baseline justify-center gap-x-1 text-base font-medium sm:text-xl lg:justify-start"
                        style="color: var(--ih-text-muted);"
                    >
                        @if (trim((string) ($ih['typing']['prefix'] ?? '')) !== '')
                            <span class="shrink-0">{{ $ih['typing']['prefix'] }}</span>
                        @endif
                        <span
                            id="{{ $uid }}-typed"
                            class="inline-block min-h-[1.25em] max-w-full break-words border-e-4 pe-3 text-center font-semibold align-baseline max-lg:text-center lg:text-start"
                            style="border-color: var(--ih-accent); color: var(--ih-accent);"
                        ></span>
                    </div>

                    @if (trim((string) ($ih['description'] ?? '')) !== '')
                        <p class="mb-10 w-full max-w-full whitespace-normal break-words text-base leading-relaxed sm:text-lg" style="color: var(--ih-text-muted);">
                            {{ $ih['description'] }}
                        </p>
                    @endif

                    <div class="mb-8 flex w-full max-w-full flex-wrap justify-center gap-4 sm:gap-5 lg:justify-start">
                        @if (trim((string) ($ih['primary_cta']['label'] ?? '')) !== '' && trim((string) ($ih['primary_cta']['url'] ?? '')) !== '')
                            <a
                                href="{{ e($ih['primary_cta']['url']) }}"
                                class="ih-btn-primary inline-flex max-w-full items-center justify-center gap-3 whitespace-normal break-words rounded-full px-8 py-4 text-center text-base font-bold text-white shadow-lg transition hover:-translate-y-0.5 hover:shadow-xl sm:px-10"
                                style="background: linear-gradient(135deg, var(--ih-accent), var(--ih-accent-2));"
                            >
                                @if (($fx['font_awesome'] ?? false) && trim((string) ($ih['primary_cta']['icon'] ?? '')) !== '')
                                    <i class="{{ e($ih['primary_cta']['icon']) }}"></i>
                                @endif
                                {{ $ih['primary_cta']['label'] }}
                            </a>
                        @endif

                        @if (($ih['secondary_cta']['enabled'] ?? false) && trim((string) ($ih['secondary_cta']['label'] ?? '')) !== '' && trim((string) ($ih['secondary_cta']['url'] ?? '')) !== '')
                            <a
                                href="{{ e($ih['secondary_cta']['url']) }}"
                                class="inline-flex max-w-full items-center justify-center gap-3 whitespace-normal break-words rounded-full border border-white/20 bg-white/5 px-8 py-4 text-center text-base font-bold text-white backdrop-blur-md transition hover:-translate-y-0.5 hover:border-violet-400/50 hover:bg-white/10 sm:px-10"
                            >
                                @if (($fx['font_awesome'] ?? false) && trim((string) ($ih['secondary_cta']['icon'] ?? '')) !== '')
                                    <i class="{{ e($ih['secondary_cta']['icon']) }}"></i>
                                @endif
                                {{ $ih['secondary_cta']['label'] }}
                            </a>
                        @endif
                    </div>
                </div>

                @if ($fx['visual_cards'] ?? false)
                    <div class="ih-visual relative hidden overflow-visible perspective-1000 lg:block" style="perspective: 1000px;">
                        <div class="relative mx-auto h-[500px] max-w-sm">
                            @foreach ($cards as $ci => $card)
                                @php
                                    $rot = $ci === 0 ? '5deg' : ($ci === 1 ? '-3deg' : '2deg');
                                    $top = $ci === 0 ? '20px' : ($ci === 1 ? '120px' : '220px');
                                    $inset = $ci === 0 ? '0' : ($ci === 1 ? '50px' : '100px');
                                @endphp
                                <div
                                    class="ih-event-card absolute w-[280px] cursor-pointer rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur-xl transition-transform duration-300 hover:z-20 hover:scale-105"
                                    style="top: {{ $top }}; inset-inline-end: {{ $inset }}; transform: rotate({{ $rot }}); animation: ih-float-{{ $ci }} 4s ease-in-out infinite; animation-delay: {{ $ci }}s;"
                                >
                                    <div class="mb-4 flex items-center gap-3">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl text-2xl text-white" style="background: linear-gradient(135deg, var(--ih-accent), var(--ih-accent-2));">
                                            @if (($fx['font_awesome'] ?? false) && trim((string) ($card['icon'] ?? '')) !== '')
                                                <i class="{{ e($card['icon']) }}"></i>
                                            @else
                                                <span class="text-lg font-bold">★</span>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="text-xs" style="color: var(--ih-text-muted);">{{ $card['date_line'] ?? '' }}</div>
                                            <div class="break-words text-lg font-bold leading-snug" style="color: var(--ih-text);">{{ $card['title'] ?? '' }}</div>
                                        </div>
                                    </div>
                                    <div class="mt-4 flex items-center gap-2 text-xs" style="color: var(--ih-text-muted);">
                                        <div class="flex -space-x-2 rtl:space-x-reverse">
                                            <span class="flex h-7 w-7 items-center justify-center rounded-full border-2 border-black/30 text-[10px] font-bold text-white" style="background: linear-gradient(135deg, var(--ih-accent), var(--ih-accent-2));">A</span>
                                            <span class="flex h-7 w-7 items-center justify-center rounded-full border-2 border-black/30 text-[10px] font-bold text-white" style="background: linear-gradient(135deg, var(--ih-accent-2), var(--ih-accent));">B</span>
                                            <span class="flex h-7 w-7 items-center justify-center rounded-full border-2 border-black/30 text-[10px] font-bold text-white" style="background: linear-gradient(135deg, var(--ih-accent), var(--ih-accent-2));">C</span>
                                        </div>
                                        <span>{{ $card['attendees'] ?? '' }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            @if ($fx['scroll_hint'] ?? false)
                @php $scrollText = trim((string) ($ih['scroll_hint']['text'] ?? '')); @endphp
                @if ($scrollText !== '')
                    <div class="relative z-10 mt-10 flex w-full justify-center lg:mt-12">
                        <button
                            type="button"
                            class="ih-scroll-hint cursor-pointer border-0 bg-transparent text-center"
                            data-scroll-next
                            aria-label="{{ $scrollText }}"
                        >
                            <div class="mx-auto mb-2 h-[50px] w-[30px] rounded-[20px] border-2 border-white/30">
                                <span class="mx-auto mt-2 block h-3 w-1 animate-bounce rounded-sm bg-white/80"></span>
                            </div>
                            <span class="text-xs" style="color: rgba(255,255,255,0.5);">{{ $scrollText }}</span>
                        </button>
                    </div>
                @endif
            @endif
    </div>
</section>

@pushOnce('styles', 'shop-immersive-hero-css')
    <style>
        .ih-orb-1 { width: 500px; height: 500px; background: radial-gradient(circle, var(--ih-orb-1), transparent 70%); top: -200px; inset-inline-end: -200px; animation: ih-float-orb 20s ease-in-out infinite; }
        .ih-orb-2 { width: 600px; height: 600px; background: radial-gradient(circle, var(--ih-orb-2), transparent 70%); bottom: -300px; inset-inline-start: -200px; animation: ih-float-orb 20s ease-in-out infinite; animation-delay: -5s; }
        .ih-orb-3 { width: 400px; height: 400px; background: radial-gradient(circle, var(--ih-orb-3), transparent 70%); top: 50%; left: 50%; animation: ih-float-orb-3 20s ease-in-out infinite; animation-delay: -10s; }
        @keyframes ih-float-orb {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -30px) scale(1.05); }
            66% { transform: translate(-20px, 25px) scale(0.95); }
        }
        @keyframes ih-float-orb-3 {
            0%, 100% { transform: translate(-50%, -50%) scale(1); }
            33% { transform: translate(calc(-50% + 30px), calc(-50% - 30px)) scale(1.05); }
            66% { transform: translate(calc(-50% - 20px), calc(-50% + 25px)) scale(0.95); }
        }
        @keyframes ih-float-0 { 0%, 100% { transform: translateY(0) rotate(5deg); } 50% { transform: translateY(-12px) rotate(7deg); } }
        @keyframes ih-float-1 { 0%, 100% { transform: translateY(0) rotate(-3deg); } 50% { transform: translateY(-12px) rotate(-5deg); } }
        @keyframes ih-float-2 { 0%, 100% { transform: translateY(0) rotate(2deg); } 50% { transform: translateY(-12px) rotate(4deg); } }
    </style>
@endPushOnce

@pushOnce('scripts', 'shop-immersive-hero-js')
    <script>
        (function () {
            function decodeCfg(el) {
                var b64 = el.getAttribute('data-immersive-hero-config');
                if (!b64) return null;
                try {
                    var bin = atob(b64);
                    var jsonStr;
                    if (typeof TextDecoder !== 'undefined') {
                        var len = bin.length;
                        var bytes = new Uint8Array(len);
                        for (var i = 0; i < len; i++) {
                            bytes[i] = bin.charCodeAt(i) & 0xff;
                        }
                        jsonStr = new TextDecoder('utf-8').decode(bytes);
                    } else {
                        jsonStr = decodeURIComponent(escape(bin));
                    }
                    return JSON.parse(jsonStr);
                } catch (e) {
                    return null;
                }
            }

            function initOne(root) {
                var cfg = decodeCfg(root);
                if (!cfg) return;
                var uid = root.id || '';

                var canvas = document.getElementById(uid + '-particles');
                var wrap = root.querySelector('.ih-hero');
                if (canvas && wrap && cfg.particles) {
                    var ctx = canvas.getContext('2d');
                    var parts = [];
                    var n = cfg.count || 80;
                    function resize() {
                        canvas.width = wrap.clientWidth || 1;
                        canvas.height = wrap.clientHeight || 1;
                        parts = [];
                        for (var i = 0; i < n; i++) {
                            parts.push({
                                x: Math.random() * canvas.width,
                                y: Math.random() * canvas.height,
                                r: Math.random() * 2 + 1,
                                a: Math.random() * 0.5 + 0.15,
                                vx: (Math.random() - 0.5) * 0.5,
                                vy: (Math.random() - 0.5) * 0.5
                            });
                        }
                    }
                    function loop() {
                        if (!ctx || !canvas.width) return requestAnimationFrame(loop);
                        ctx.clearRect(0, 0, canvas.width, canvas.height);
                        var rgb = '139, 92, 246';
                        parts.forEach(function (p) {
                            p.x += p.vx; p.y += p.vy;
                            if (p.x < 0) p.x = canvas.width;
                            if (p.x > canvas.width) p.x = 0;
                            if (p.y < 0) p.y = canvas.height;
                            if (p.y > canvas.height) p.y = 0;
                            ctx.beginPath();
                            ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                            ctx.fillStyle = 'rgba(' + rgb + ',' + p.a + ')';
                            ctx.fill();
                        });
                        requestAnimationFrame(loop);
                    }
                    resize();
                    loop();
                    window.addEventListener('resize', resize);
                }

                var typedEl = document.getElementById(uid + '-typed');
                if (typedEl && cfg.words && cfg.words.length) {
                    var wi = 0, ci = 0, del = false;
                    function tickType() {
                        var w = cfg.words[wi] || '';
                        if (del) {
                            typedEl.textContent = w.substring(0, ci - 1);
                            ci--;
                        } else {
                            typedEl.textContent = w.substring(0, ci + 1);
                            ci++;
                        }
                        if (!del && ci === w.length) {
                            del = true;
                            setTimeout(tickType, 2000);
                            return;
                        }
                        if (del && ci === 0) {
                            del = false;
                            wi = (wi + 1) % cfg.words.length;
                            setTimeout(tickType, 400);
                            return;
                        }
                        setTimeout(tickType, del ? 45 : 95);
                    }
                    tickType();
                }

                var cur = root.querySelector('.ih-cursor');
                var curF = root.querySelector('.ih-cursor-follower');
                if (cur && curF && window.matchMedia('(pointer: fine)').matches) {
                    cur.classList.remove('hidden');
                    curF.classList.remove('hidden');
                    document.addEventListener('mousemove', function (e) {
                        cur.style.left = e.clientX + 'px';
                        cur.style.top = e.clientY + 'px';
                        curF.style.left = e.clientX + 'px';
                        curF.style.top = e.clientY + 'px';
                    });
                }

                var scrollB = root.querySelector('[data-scroll-next]');
                if (scrollB) {
                    scrollB.addEventListener('click', function () {
                        window.scrollTo({ top: window.innerHeight, behavior: 'smooth' });
                    });
                }
            }

            function boot() {
                document.querySelectorAll('.ih-root[data-immersive-hero-config]').forEach(initOne);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', boot);
            } else {
                boot();
            }
        })();
    </script>
@endPushOnce
