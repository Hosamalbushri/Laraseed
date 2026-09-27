@props([
    'options' => [],
])

@php
    use Illuminate\Support\Str;

    $defaults = [
        'enabled' => true,
        'effects' => [
            'orbs' => true,
            'grid' => true,
            'parallax' => true,
            'font_awesome' => true,
            'back_to_top' => true,
        ],
        'colors' => [
            'bg_start' => '#0a0a2a',
            'bg_end' => '#030318',
            'accent' => '#8b5cf6',
            'accent_2' => '#6366f1',
            'border_top' => 'rgba(139, 92, 246, 0.2)',
            'text' => '#ffffff',
            'text_muted' => 'rgba(255,255,255,0.6)',
            'orb_1' => 'rgba(139, 92, 246, 0.6)',
            'orb_2' => 'rgba(236, 72, 153, 0.5)',
            'orb_3' => 'rgba(59, 130, 246, 0.4)',
        ],
        'brand' => ['logo_path' => '', 'logo_icon' => 'fas fa-graduation-cap', 'title' => '', 'description' => ''],
        'social' => [],
        'col_quick' => ['title' => '', 'show_chevron' => true, 'links' => []],
        'contact' => ['title' => '', 'items' => []],
        'newsletter' => [
            'enabled' => false,
            'title' => '',
            'text' => '',
            'placeholder' => '',
            'button_label' => '',
            'form_action' => '',
            'form_method' => 'post',
        ],
        'bottom' => ['copyright' => '', 'links' => []],
    ];

    $pf = array_replace_recursive($defaults, is_array($options) ? $options : []);
    $fx = $pf['effects'];
    $c = $pf['colors'];
    $uid = 'pf-' . Str::random(8);

    $copyrightLine = trim((string) ($pf['bottom']['copyright'] ?? ''));
    if ($copyrightLine === '') {
        $copyrightLine = '© :year';
    }
    $copyrightLine = str_replace(':year', (string) date('Y'), $copyrightLine);

    $newsAction = trim((string) ($pf['newsletter']['form_action'] ?? ''));
    $needsCsrf = $newsAction !== '' && (str_starts_with($newsAction, '/') || str_starts_with($newsAction, url('/')));

    $parallaxOn = ($fx['parallax'] ?? false) && ($fx['orbs'] ?? false);
    $isRtl = in_array(app()->getLocale(), ['ar', 'fa'], true);

    $brandLogoPath = trim((string) ($pf['brand']['logo_path'] ?? ''));
    $brandLogoUrl = '';
    if ($brandLogoPath !== '') {
        $brandLogoUrl = \Illuminate\Support\Facades\Storage::disk('public')->exists($brandLogoPath)
            ? \Illuminate\Support\Facades\Storage::url($brandLogoPath)
            : '';
    }
@endphp

{{-- footer: overflow visible so fixed back-to-top is not clipped --}}
<footer
    class="pf-root relative mt-9 w-full"
    id="{{ $uid }}"
    role="contentinfo"
    data-pf-parallax="{{ $parallaxOn ? '1' : '0' }}"
    style="
        --pf-bg-start: {{ $c['bg_start'] }};
        --pf-bg-end: {{ $c['bg_end'] }};
        --pf-accent: {{ $c['accent'] }};
        --pf-accent-2: {{ $c['accent_2'] }};
        --pf-border-top: {{ $c['border_top'] }};
        --pf-text: {{ $c['text'] }};
        --pf-text-muted: {{ $c['text_muted'] }};
        --pf-orb-1: {{ $c['orb_1'] }};
        --pf-orb-2: {{ $c['orb_2'] }};
        --pf-orb-3: {{ $c['orb_3'] }};
        border-top: 1px solid var(--pf-border-top);
        background: linear-gradient(180deg, var(--pf-bg-start) 0%, var(--pf-bg-end) 100%);
    "
>
    <div class="relative w-full overflow-hidden">
        @if ($fx['orbs'] ?? false)
            <div class="pf-orb pf-orb-1 pf-bg-orb pointer-events-none absolute rounded-full opacity-40 blur-[100px] {{ $parallaxOn ? 'pf-orb-no-anim' : '' }}" aria-hidden="true"></div>
            <div class="pf-orb pf-orb-2 pf-bg-orb pointer-events-none absolute rounded-full opacity-40 blur-[100px] {{ $parallaxOn ? 'pf-orb-no-anim' : '' }}" aria-hidden="true"></div>
            <div class="pf-orb pf-orb-3 pf-bg-orb pointer-events-none absolute rounded-full opacity-40 blur-[100px] {{ $parallaxOn ? 'pf-orb-no-anim' : '' }}" aria-hidden="true"></div>
        @endif

        @if ($fx['grid'] ?? false)
            <div class="pointer-events-none absolute inset-0 opacity-100" style="background-image: linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px); background-size: 50px 50px;" aria-hidden="true"></div>
        @endif

        <div class="relative z-10 mx-auto w-full max-w-[1400px] px-4 py-8 sm:px-6 lg:px-10 lg:py-10">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-3 md:gap-6 lg:gap-8">
                {{-- Brand --}}
                <div class="pf-footer-col min-w-0 text-center">
                    @if ($brandLogoUrl !== '' || trim((string) ($pf['brand']['title'] ?? '')) !== '' || trim((string) ($pf['brand']['description'] ?? '')) !== '' || (($fx['font_awesome'] ?? false) && trim((string) ($pf['brand']['logo_icon'] ?? '')) !== ''))
                        <div class="group mb-3 flex flex-col items-center gap-2">
                            @if ($brandLogoUrl !== '')
                                <img
                                    class="pf-brand-logo-img max-h-12 w-auto max-w-[200px] object-contain object-center transition duration-300"
                                    src="{{ $brandLogoUrl }}"
                                    alt="{{ trim((string) ($pf['brand']['title'] ?? '')) !== '' ? e($pf['brand']['title']) : '' }}"
                                    width="220"
                                    height="56"
                                    loading="lazy"
                                    decoding="async"
                                >
                            @elseif (($fx['font_awesome'] ?? false) && trim((string) ($pf['brand']['logo_icon'] ?? '')) !== '')
                                <span class="pf-logo-icon flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-2xl text-white shadow-lg transition duration-300" style="background: linear-gradient(135deg, var(--pf-accent), var(--pf-accent-2)); box-shadow: 0 10px 30px color-mix(in srgb, var(--pf-accent) 35%, transparent);">
                                    <i class="{{ e($pf['brand']['logo_icon']) }}" aria-hidden="true"></i>
                                </span>
                            @endif
                            @if (trim((string) ($pf['brand']['title'] ?? '')) !== '')
                                <span class="bg-gradient-to-br from-white to-violet-300 bg-clip-text text-xl font-extrabold text-transparent sm:text-2xl">{{ $pf['brand']['title'] }}</span>
                            @endif
                        </div>
                    @endif
                    @if (trim((string) ($pf['brand']['description'] ?? '')) !== '')
                        <p class="mb-4 mx-auto max-w-md text-sm leading-relaxed sm:text-base" style="color: var(--pf-text-muted);">{{ $pf['brand']['description'] }}</p>
                    @endif
                    @if (count($pf['social'] ?? []) > 0)
                        <div class="flex flex-wrap justify-center gap-2">
                            @foreach ($pf['social'] as $soc)
                                @php $sUrl = trim((string) ($soc['url'] ?? '')); $sIcon = trim((string) ($soc['icon'] ?? '')); @endphp
                                @if ($sUrl !== '' && ($fx['font_awesome'] ?? false) && $sIcon !== '')
                                    <a
                                        href="{{ e($sUrl) }}"
                                        class="pf-social flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white transition duration-300"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label="{{ __('shop::app.components.layouts.footer.portal.social-profile') }}"
                                    >
                                        <i class="{{ e($sIcon) }}" aria-hidden="true"></i>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>

                @foreach (['col_quick' => $pf['col_quick']] as $colKey => $col)
                    <div class="pf-footer-col min-w-0 text-center">
                        @if (trim((string) ($col['title'] ?? '')) !== '')
                            <h3 class="mb-4 text-lg font-bold" style="color: var(--pf-text);">{{ $col['title'] }}</h3>
                        @endif
                        @if (count($col['links'] ?? []) > 0)
                            <ul class="mx-auto flex w-full max-w-xs flex-col items-center gap-2 text-sm text-center">
                                @foreach ($col['links'] as $link)
                                    @php $lab = trim((string) ($link['label'] ?? '')); $u = trim((string) ($link['url'] ?? '')); @endphp
                                    @if ($lab !== '')
                                        <li class="w-full">
                                            @if ($u !== '')
                                                <a href="{{ e($u) }}" class="pf-column-link inline-flex w-full items-center justify-center gap-2 transition duration-200" style="color: var(--pf-text-muted);">
                                                    @if (! empty($col['show_chevron']) && ($fx['font_awesome'] ?? false))
                                                        <i class="fas {{ $isRtl ? 'fa-chevron-left' : 'fa-chevron-right' }} text-[10px] opacity-70 transition-transform duration-200" aria-hidden="true"></i>
                                                    @endif
                                                    {{ $lab }}
                                                </a>
                                            @else
                                                <span class="block text-center" style="color: var(--pf-text-muted);">{{ $lab }}</span>
                                            @endif
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach

                {{-- Contact + newsletter --}}
                <div class="pf-footer-col min-w-0 text-center">
                    @if (trim((string) ($pf['contact']['title'] ?? '')) !== '')
                        <h3 class="mb-4 text-lg font-bold" style="color: var(--pf-text);">{{ $pf['contact']['title'] }}</h3>
                    @endif
                    @if (count($pf['contact']['items'] ?? []) > 0)
                        <ul class="mb-4 flex flex-col items-center gap-3 text-sm">
                            @foreach ($pf['contact']['items'] as $item)
                                @php $ti = trim((string) ($item['text'] ?? '')); $ic = trim((string) ($item['icon'] ?? '')); @endphp
                                @if ($ti !== '')
                                    <li class="pf-contact-row flex max-w-md flex-col items-center gap-2 transition duration-200" style="color: var(--pf-text-muted);">
                                        @if (($fx['font_awesome'] ?? false) && $ic !== '')
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full transition duration-200" style="color: var(--pf-accent); background: color-mix(in srgb, var(--pf-accent) 12%, transparent);">
                                                <i class="{{ e($ic) }}" aria-hidden="true"></i>
                                            </span>
                                        @endif
                                        <span class="leading-relaxed">{{ $ti }}</span>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    @endif

                    @if (($pf['newsletter']['enabled'] ?? false) && (trim((string) ($pf['newsletter']['title'] ?? '')) !== '' || trim((string) ($pf['newsletter']['text'] ?? '')) !== '' || trim((string) ($pf['newsletter']['placeholder'] ?? '')) !== '' || trim((string) ($pf['newsletter']['button_label'] ?? '')) !== ''))
                        <div class="mx-auto w-full max-w-sm">
                        @if (trim((string) ($pf['newsletter']['title'] ?? '')) !== '')
                            <h3 class="mb-2 text-lg font-bold" style="color: var(--pf-text);">{{ $pf['newsletter']['title'] }}</h3>
                        @endif
                        @if (trim((string) ($pf['newsletter']['text'] ?? '')) !== '')
                            <p class="mb-3 text-sm leading-relaxed" style="color: var(--pf-text-muted);">{{ $pf['newsletter']['text'] }}</p>
                        @endif
                        @if ($newsAction !== '')
                            <form class="flex flex-col items-stretch gap-2 sm:flex-row sm:items-center sm:justify-center" action="{{ e($newsAction) }}" method="{{ in_array(strtolower((string) ($pf['newsletter']['form_method'] ?? 'post')), ['get', 'post'], true) ? strtolower((string) $pf['newsletter']['form_method']) : 'post' }}" accept-charset="UTF-8">
                                @if ($needsCsrf)
                                    @csrf
                                @endif
                                <input type="email" name="email" required class="pf-input min-w-0 flex-1 rounded-full border border-white/10 bg-white/5 px-4 py-2.5 text-sm outline-none transition duration-200" style="color: var(--pf-text);" placeholder="{{ e($pf['newsletter']['placeholder'] ?? '') }}" autocomplete="email">
                                @if (trim((string) ($pf['newsletter']['button_label'] ?? '')) !== '')
                                    <button type="submit" class="pf-btn-primary inline-flex shrink-0 items-center justify-center gap-2 rounded-full px-5 py-2.5 text-sm font-bold text-white transition duration-200" style="background: linear-gradient(135deg, var(--pf-accent), var(--pf-accent-2));">
                                        @if ($fx['font_awesome'] ?? false)
                                            <i class="fas fa-paper-plane" aria-hidden="true"></i>
                                        @endif
                                        {{ $pf['newsletter']['button_label'] }}
                                    </button>
                                @endif
                            </form>
                        @else
                            <div class="flex flex-col items-stretch gap-2 sm:flex-row sm:items-center sm:justify-center">
                                <input type="email" id="{{ $uid }}-nl-email" class="pf-input min-w-0 flex-1 rounded-full border border-white/10 bg-white/5 px-4 py-2.5 text-sm outline-none transition duration-200" style="color: var(--pf-text);" placeholder="{{ e($pf['newsletter']['placeholder'] ?? '') }}" autocomplete="email">
                                @if (trim((string) ($pf['newsletter']['button_label'] ?? '')) !== '')
                                    <button type="button" class="pf-nl-demo pf-btn-primary inline-flex shrink-0 items-center justify-center gap-2 rounded-full px-5 py-2.5 text-sm font-bold text-white transition duration-200" style="background: linear-gradient(135deg, var(--pf-accent), var(--pf-accent-2));" data-msg="{{ e(__('shop::app.components.layouts.footer.portal.newsletter-demo')) }}">
                                        @if ($fx['font_awesome'] ?? false)
                                            <i class="fas fa-paper-plane" aria-hidden="true"></i>
                                        @endif
                                        {{ $pf['newsletter']['button_label'] }}
                                    </button>
                                @endif
                            </div>
                        @endif
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-8 flex flex-col items-center gap-4 border-t border-white/10 pt-5 sm:flex-row sm:justify-center sm:gap-8">
                <p class="text-center text-sm" style="color: rgba(255,255,255,0.5);">{!! nl2br(e($copyrightLine)) !!}</p>
                @if (count($pf['bottom']['links'] ?? []) > 0)
                    <nav class="flex flex-wrap justify-center gap-3" aria-label="{{ __('shop::app.components.layouts.footer.portal.bottom-nav') }}">
                        @foreach ($pf['bottom']['links'] as $bl)
                            @php $blLabel = trim((string) ($bl['label'] ?? '')); $blUrl = trim((string) ($bl['url'] ?? '')); @endphp
                            @if ($blLabel !== '' && $blUrl !== '')
                                <a href="{{ e($blUrl) }}" class="pf-bottom-link text-sm transition duration-200" style="color: rgba(255,255,255,0.5);">{{ $blLabel }}</a>
                            @endif
                        @endforeach
                    </nav>
                @endif
            </div>
        </div>
    </div>

    @if ($fx['back_to_top'] ?? false)
        <button
            type="button"
            class="pf-back-top"
            data-pf-back-top="1"
            aria-label="{{ __('shop::app.components.layouts.footer.portal.back-top') }}"
            style="background: linear-gradient(135deg, var(--pf-accent), var(--pf-accent-2));"
        >
            @if ($fx['font_awesome'] ?? false)
                <i class="fas fa-arrow-up text-lg" aria-hidden="true"></i>
            @else
                <span class="text-lg leading-none">↑</span>
            @endif
        </button>
    @endif
</footer>

@pushOnce('styles', 'shop-portal-footer-css')
    <style>
        /* Orbs float (disabled when parallax applies inline transform) */
        .pf-orb-1 { width: 500px; height: 500px; background: radial-gradient(circle, var(--pf-orb-1), transparent 70%); bottom: -200px; inset-inline-start: -150px; animation: pf-float-orb 25s ease-in-out infinite; }
        .pf-orb-2 { width: 400px; height: 400px; background: radial-gradient(circle, var(--pf-orb-2), transparent 70%); top: -150px; inset-inline-end: -100px; animation: pf-float-orb 20s ease-in-out infinite reverse; }
        .pf-orb-3 { width: 300px; height: 300px; background: radial-gradient(circle, var(--pf-orb-3), transparent 70%); bottom: 30%; inset-inline-end: 20%; animation: pf-float-orb 18s ease-in-out infinite; animation-delay: -5s; }
        .pf-orb.pf-orb-no-anim { animation: none; }
        @keyframes pf-float-orb {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(40px, -30px) scale(1.05); }
            66% { transform: translate(-30px, 40px) scale(0.95); }
        }

        .pf-root .group:hover .pf-logo-icon { transform: rotate(5deg) scale(1.05); }
        .pf-root .group:hover .pf-brand-logo-img { transform: scale(1.03); filter: brightness(1.06); }

        .pf-social:hover {
            background: linear-gradient(135deg, var(--pf-accent), var(--pf-accent-2)) !important;
            border-color: transparent !important;
            transform: translateY(-4px) scale(1.08);
            box-shadow: 0 10px 25px color-mix(in srgb, var(--pf-accent) 45%, transparent);
        }

        .pf-column-link:hover {
            color: var(--pf-accent) !important;
            transform: translateY(-2px);
        }
        .pf-column-link:hover i { transform: translateY(-1px); }

        .pf-contact-row:hover { color: rgba(255, 255, 255, 0.92) !important; transform: translateY(-2px); }
        .pf-contact-row:hover span.flex { transform: scale(1.08); }

        .pf-input:focus {
            border-color: var(--pf-accent) !important;
            background: color-mix(in srgb, var(--pf-accent) 8%, transparent) !important;
            box-shadow: 0 0 0 2px color-mix(in srgb, var(--pf-accent) 25%, transparent);
        }

        .pf-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px color-mix(in srgb, var(--pf-accent) 45%, transparent);
            filter: brightness(1.05);
        }

        .pf-bottom-link:hover {
            color: var(--pf-accent) !important;
        }

        /* Fixed button: opposite side from default (start = left in LTR, right in RTL) */
        .pf-back-top {
            position: fixed;
            bottom: 1.5rem;
            inset-inline-start: 1.5rem;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            border: none;
            border-radius: 9999px;
            color: #fff;
            cursor: pointer;
            box-shadow: 0 5px 20px color-mix(in srgb, var(--pf-accent) 40%, transparent);
            opacity: 0;
            visibility: hidden;
            transform: translateY(12px);
            transition: opacity 0.3s ease, visibility 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease;
        }
        .pf-back-top.pf-back-top--visible {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .pf-back-top:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px color-mix(in srgb, var(--pf-accent) 55%, transparent);
        }
        .pf-back-top.pf-back-top--visible:hover {
            transform: translateY(-4px);
        }
    </style>
@endPushOnce

@pushOnce('scripts', 'shop-portal-footer-js')
    <script>
        (function () {
            function initPortalFooter(root) {
                if (!root || !root.id) return;
                var rootId = root.id;
                if (root.getAttribute('data-pf-parallax') === '1') {
                    document.addEventListener('mousemove', function (e) {
                        var mx = e.clientX / window.innerWidth;
                        var my = e.clientY / window.innerHeight;
                        root.querySelectorAll('.pf-bg-orb').forEach(function (orb, i) {
                            var moveX = (mx - 0.5) * 18 * (i + 1);
                            var moveY = (my - 0.5) * 18 * (i + 1);
                            orb.style.transform = 'translate(' + moveX + 'px,' + moveY + 'px) scale(1)';
                        });
                    });
                }
                var demoBtn = root.querySelector('.pf-nl-demo');
                if (demoBtn) {
                    demoBtn.addEventListener('click', function () {
                        var inp = document.getElementById(rootId + '-nl-email');
                        var msg = demoBtn.getAttribute('data-msg') || '';
                        if (inp && inp.value && inp.value.indexOf('@') !== -1) {
                            window.alert(msg);
                            inp.value = '';
                        } else if (inp) {
                            inp.focus();
                        }
                    });
                }
                var backBtn = root.querySelector('.pf-back-top');
                if (backBtn) {
                    function syncBackTop() {
                        var y = window.scrollY || window.pageYOffset || document.documentElement.scrollTop || 0;
                        if (y > 280) {
                            backBtn.classList.add('pf-back-top--visible');
                        } else {
                            backBtn.classList.remove('pf-back-top--visible');
                        }
                    }
                    syncBackTop();
                    window.addEventListener('scroll', syncBackTop, { passive: true });
                    backBtn.addEventListener('click', function () {
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    });
                }
            }
            function boot() {
                document.querySelectorAll('.pf-root[id]').forEach(initPortalFooter);
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', boot);
            } else {
                boot();
            }
        })();
    </script>
@endPushOnce
