{!! view_render_event('shop.components.layouts.header.desktop.bottom.before') !!}

@php
    $studentLoginUrl = config('shop.student_login_url', '#');
    if ($studentLoginUrl !== '#' && $studentLoginUrl !== '' && ! \Illuminate\Support\Str::startsWith($studentLoginUrl, ['http://', 'https://'])) {
        $studentLoginUrl = url($studentLoginUrl);
    }
    $cfgLogoPath = core()->getConfigData('general.store.shop.logo_image')
        ?: core()->getConfigData('general.design.shop.logo_image');
    $logoUrl = $cfgLogoPath ? \Illuminate\Support\Facades\Storage::url($cfgLogoPath) : config('shop.logo_url');
    $middleLogoPath = core()->getConfigData('general.store.shop.header_middle_logo');
    $middleLogoUrl = $middleLogoPath ? \Illuminate\Support\Facades\Storage::url($middleLogoPath) : null;

    $homeLabel = trim((string) core()->getConfigData('general.store.navigation.home_label'));
    $eventsLabel = trim((string) core()->getConfigData('general.store.navigation.events_label'));
    $navItems = [];

    if (filter_var(core()->getConfigData('general.store.navigation.show_home'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true) {
        $navItems[] = [
            'label' => $homeLabel !== '' ? $homeLabel : __('shop::app.layout.nav.home'),
            'route' => 'shop.home.index',
        ];
    }

    if (filter_var(core()->getConfigData('general.store.navigation.show_events'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true) {
        $navItems[] = [
            'label' => $eventsLabel !== '' ? $eventsLabel : __('shop::app.layout.nav.events'),
            'route' => 'shop.events.index',
        ];
    }

    foreach ([1, 2] as $i) {
        if (! core()->getConfigData("general.store.navigation.custom_{$i}_enabled")) {
            continue;
        }

        $label = trim((string) core()->getConfigData("general.store.navigation.custom_{$i}_label"));
        $url = trim((string) core()->getConfigData("general.store.navigation.custom_{$i}_url"));

        if ($label === '' || $url === '') {
            continue;
        }

        $navItems[] = [
            'label' => $label,
            'url' => \Illuminate\Support\Str::startsWith($url, ['http://', 'https://']) ? $url : url($url),
        ];
    }

    $isMenuRouteActive = function (string $routeName): bool {
        if (request()->routeIs($routeName)) {
            return true;
        }

        if (\Illuminate\Support\Str::endsWith($routeName, '.index')) {
            $base = \Illuminate\Support\Str::beforeLast($routeName, '.index');

            return request()->routeIs($base.'.*');
        }

        return false;
    };

    $isMenuItemActive = function (array $item) use ($isMenuRouteActive): bool {
        if (! empty($item['route'])) {
            return $isMenuRouteActive($item['route']);
        }

        if (! empty($item['url'])) {
            return rtrim((string) request()->url(), '/') === rtrim((string) $item['url'], '/');
        }

        return false;
    };
@endphp

<div class="flex min-h-[78px] w-full items-center gap-4 border border-b border-l-0 border-r-0 border-t-0 px-[60px] max-1180:px-8">
    {{-- Left: logo + nav --}}
    <div class="flex min-w-0 flex-shrink-0 items-center gap-x-8 max-[1180px]:gap-x-5">
        {!! view_render_event('shop.components.layouts.header.desktop.bottom.logo.before') !!}

        <a
            href="{{ route('shop.home.index') }}"
            class="flex items-center gap-3"
            aria-label="{{ __('shop::app.components.layouts.header.desktop.bottom.logo-alt') }}"
        >
            @if ($logoUrl)
                <img
                    src="{{ $logoUrl }}"
                    width="131"
                    height="29"
                    alt="{{ config('app.name') }}"
                >
            @else
                <span class="font-dmserif text-2xl font-medium text-navyBlue max-sm:text-xl">
                    {{ __('shop::app.layout.brand') }}
                </span>
            @endif
        </a>

        {!! view_render_event('shop.components.layouts.header.desktop.bottom.logo.after') !!}

        <nav class="flex items-center gap-5 max-lg:hidden" aria-label="{{ __('shop::app.components.layouts.header.desktop.bottom.nav-label') }}">
            @foreach ($navItems as $item)
                @php $isActive = $isMenuItemActive($item); @endphp
                <a
                    href="{{ ! empty($item['route']) ? route($item['route']) : ($item['url'] ?? '#') }}"
                    class="{{ $isActive ? 'border border-[color:var(--shop-primary)] bg-[color:var(--shop-primary)] text-white shadow-sm' : 'border border-transparent bg-transparent text-navyBlue hover:border-[color:var(--shop-border-soft)] hover:bg-[color:var(--shop-surface)]' }} rounded-md px-2.5 py-1 text-sm font-medium uppercase transition"
                    @if ($isActive) aria-current="page" @endif
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
    </div>

    {{-- Center: search --}}
    <div class="flex min-w-0 flex-1 justify-center px-4">
        {!! view_render_event('shop.components.layouts.header.desktop.bottom.search_bar.before') !!}

        <div class="relative w-full max-w-[445px]">
            <form
                action="{{ route('shop.events.index') }}"
                class="flex w-full items-center justify-center"
                role="search"
                method="GET"
            >
                <label
                    for="portal-search"
                    class="sr-only"
                >
                    {{ __('shop::app.components.layouts.header.desktop.bottom.search') }}
                </label>

                <div class="icon-search pointer-events-none absolute top-2.5 flex items-center text-xl ltr:left-3 rtl:right-3"></div>

                <input
                    id="portal-search"
                    type="text"
                    name="query"
                    value="{{ request('query') }}"
                    class="block w-full rounded-lg border border-transparent bg-zinc-100 px-11 py-3 text-xs font-medium text-gray-900 transition-all hover:border-gray-400 focus:border-gray-400"
                    placeholder="{{ __('shop::app.components.layouts.header.desktop.bottom.search-text') }}"
                    autocomplete="off"
                >
            </form>
        </div>

        {!! view_render_event('shop.components.layouts.header.desktop.bottom.search_bar.after') !!}
    </div>

    @if ($middleLogoUrl)
        <div class="flex flex-shrink-0 items-center justify-center max-1180:hidden">
            <img
                src="{{ $middleLogoUrl }}"
                alt="{{ config('app.name') }}"
                class="max-h-10 w-auto object-contain"
            >
        </div>
    @endif

    {{-- Right: student login or account --}}
    <div class="flex flex-shrink-0 items-center justify-end gap-3">
        @if (\Illuminate\Support\Facades\Route::has('student.login'))
            @auth('student')
                <x-shop::layouts.header.student-account />
            @else
                <a
                    href="{{ $studentLoginUrl }}"
                    class="primary-button !px-6 !py-3 text-sm max-md:!rounded-lg"
                >
                    {{ __('shop::app.layout.nav.student-login') }}
                </a>
            @endauth
        @endif
    </div>
</div>

{!! view_render_event('shop.components.layouts.header.desktop.bottom.after') !!}
