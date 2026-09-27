{!! view_render_event('shop.components.layouts.header.mobile.before') !!}

@php
    $studentLoginUrl = config('shop.student_login_url', '#');
    if ($studentLoginUrl !== '#' && $studentLoginUrl !== '' && ! \Illuminate\Support\Str::startsWith($studentLoginUrl, ['http://', 'https://'])) {
        $studentLoginUrl = url($studentLoginUrl);
    }
    $cfgLogoPath = core()->getConfigData('general.store.shop.logo_image')
        ?: core()->getConfigData('general.design.shop.logo_image');
    $logoUrl = $cfgLogoPath ? \Illuminate\Support\Facades\Storage::url($cfgLogoPath) : config('shop.logo_url');

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

<div class="flex flex-wrap gap-4 px-4 pb-4 pt-6 shadow-sm lg:hidden">
    <div class="grid w-full grid-cols-[1fr_auto_1fr] items-center gap-2">
        <div class="flex min-w-0 items-center justify-start">
            @php
                $menuDrawerId = 'mobile-menu-drawer';
            @endphp

            <input
                id="{{ $menuDrawerId }}"
                type="checkbox"
                class="peer hidden"
            >

            <label
                for="{{ $menuDrawerId }}"
                class="flex cursor-pointer items-center"
                aria-label="{{ __('shop::app.components.layouts.header.mobile.menu') }}"
            >
                <span class="icon-hamburger text-2xl text-navyBlue"></span>
            </label>

            <label
                for="{{ $menuDrawerId }}"
                class="fixed inset-0 z-[10002] hidden bg-black/40 opacity-0 transition-opacity peer-checked:block peer-checked:opacity-100 lg:hidden"
            ></label>

            <div
                class="fixed inset-y-0 z-[10003] w-[min(320px,86vw)] ltr:left-0 rtl:right-0 bg-white shadow-xl transition-transform duration-200 ease-in-out ltr:-translate-x-full rtl:translate-x-full peer-checked:translate-x-0"
                data-drawer="menu"
            >
                <div class="flex h-full flex-col overflow-auto border-r border-[color:var(--shop-border-soft)] bg-[color:var(--shop-surface)]">
                    <div class="flex items-center justify-between gap-3 border-b border-[color:var(--shop-border-soft)] bg-white px-4 py-3">
                        <p class="text-sm font-semibold text-[color:var(--shop-text)]">
                            {{ __('shop::app.components.layouts.header.mobile.menu') }}
                        </p>

                        <label
                            for="{{ $menuDrawerId }}"
                            class="cursor-pointer rounded-md p-2 hover:bg-[color:var(--shop-surface)]"
                            aria-label="Close"
                        >
                            <span class="text-2xl leading-none text-[color:var(--shop-text-muted)]">×</span>
                        </label>
                    </div>

                    <div class="p-4">
                        <nav class="grid w-full gap-2">
                            @foreach ($navItems as $item)
                                @php $isActive = $isMenuItemActive($item); @endphp
                                <a
                                    href="{{ ! empty($item['route']) ? route($item['route']) : ($item['url'] ?? '#') }}"
                                    class="{{ $isActive ? 'border border-[color:var(--shop-primary)] bg-[color:var(--shop-primary)] text-white shadow-sm' : 'border border-[color:var(--shop-border-soft)] bg-white text-[color:var(--shop-text)] hover:border-[color:var(--shop-border-hover)] hover:bg-[color:var(--shop-surface)]' }} rounded-lg px-4 py-2.5 text-sm font-semibold transition"
                                    @if ($isActive) aria-current="page" @endif
                                >
                                    {{ $item['label'] }}
                                </a>
                            @endforeach

                            @if (\Illuminate\Support\Facades\Route::has('student.login'))
                                @auth('student')
                                    <form
                                        method="post"
                                        action="{{ route('student.logout') }}"
                                        class="pt-2 mt-2 border-t border-[color:var(--shop-border-soft)]"
                                    >
                                        @csrf
                                        <button
                                            type="submit"
                                            class="w-full rounded-lg bg-[color:var(--shop-primary)] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[color:var(--shop-primary-hover)]"
                                        >
                                            {{ __('shop::app.layout.nav.student-logout') }}
                                        </button>
                                    </form>
                                @else
                                    <a
                                        href="{{ $studentLoginUrl }}"
                                        class="w-full rounded-lg border border-[color:var(--shop-border-soft)] bg-white px-4 py-2.5 text-sm font-semibold text-[color:var(--shop-text)] transition hover:bg-[color:var(--shop-surface)]"
                                    >
                                        {{ __('shop::app.layout.nav.student-login') }}
                                    </a>
                                @endauth
                            @endif
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <a
            href="{{ route('shop.home.index') }}"
            class="mx-auto flex max-h-[30px] min-w-0 max-w-full items-center justify-center px-2"
            aria-label="{{ __('shop::app.components.layouts.header.mobile.logo-alt') }}"
        >
            @if ($logoUrl)
                <img
                    src="{{ $logoUrl }}"
                    alt="{{ config('app.name') }}"
                    width="131"
                    height="29"
                    class="mx-auto block max-h-[30px] w-auto max-w-[min(100%,170px)] object-contain"
                >
            @else
                <span class="font-dmserif text-xl font-medium text-navyBlue">
                    {{ __('shop::app.layout.brand') }}
                </span>
            @endif
        </a>

        <div class="flex min-w-0 items-center justify-end">
            @php
                $userDrawerId = 'mobile-user-drawer';
            @endphp

            @if (\Illuminate\Support\Facades\Route::has('student.login'))
                <input
                    id="{{ $userDrawerId }}"
                    type="checkbox"
                    class="peer hidden"
                >

                @auth('student')
                    @php
                        $student = auth('student')->user();
                        $name = trim((string) ($student->name ?? ''));
                        $initial = $name !== '' ? mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8') : '?';
                        $profileImage = ! empty($student->profile_image)
                            ? \Illuminate\Support\Facades\Storage::url($student->profile_image)
                            : null;
                    @endphp

                    <label
                        for="{{ $userDrawerId }}"
                        class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-full bg-[color:var(--shop-primary)] text-sm font-semibold text-white shadow-sm transition hover:bg-[color:var(--shop-primary-hover)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--shop-ring)] focus-visible:ring-offset-2"
                        aria-label="{{ __('shop::app.layout.nav.student-menu') }}"
                    >
                        @if ($profileImage)
                            <img src="{{ $profileImage }}" alt="{{ $student->name }}" class="h-10 w-10 rounded-full object-cover">
                        @else
                            {{ $initial }}
                        @endif
                    </label>
                @else
                    <label
                        for="{{ $userDrawerId }}"
                        class="primary-button !shrink-0 !px-3 !py-2 text-[11px] cursor-pointer"
                        aria-label="{{ __('shop::app.layout.nav.student-login') }}"
                    >
                        {{ __('shop::app.layout.nav.student-login') }}
                    </label>
                @endauth

                <label
                    for="{{ $userDrawerId }}"
                    class="fixed inset-0 z-[10002] hidden bg-black/40 opacity-0 transition-opacity peer-checked:block peer-checked:opacity-100 lg:hidden"
                ></label>

                <div
                    class="fixed inset-y-0 z-[10003] w-[min(320px,86vw)] ltr:right-0 rtl:left-0 bg-white shadow-xl transition-transform duration-200 ease-in-out ltr:translate-x-full rtl:-translate-x-full peer-checked:translate-x-0"
                    data-drawer="user"
                >
                    <div class="flex h-full flex-col overflow-auto border-l border-[color:var(--shop-border-soft)] bg-[color:var(--shop-surface)]">
                        <div class="flex items-center justify-between gap-3 border-b border-[color:var(--shop-border-soft)] bg-white px-4 py-3">
                            <p class="text-sm font-semibold text-[color:var(--shop-text)]">
                                {{ __('shop::app.layout.nav.student-menu') }}
                            </p>

                            <label
                                for="{{ $userDrawerId }}"
                                class="cursor-pointer rounded-md p-2 hover:bg-[color:var(--shop-surface)]"
                                aria-label="Close"
                            >
                                <span class="text-2xl leading-none text-[color:var(--shop-text-muted)]">×</span>
                            </label>
                        </div>

                        <div class="flex-1 overflow-auto p-4">
                            @auth('student')
                                <div class="mb-4 grid gap-2">
                                    <a
                                        href="{{ route('shop.student.account.edit') }}"
                                        class="rounded-lg border border-[color:var(--shop-border-soft)] bg-white px-4 py-2.5 text-sm font-semibold text-[color:var(--shop-text)] transition hover:bg-[color:var(--shop-surface)]"
                                    >
                                        {{ __('shop::app.layout.nav.student-account-edit') }}
                                    </a>

                                    <a
                                        href="{{ route('shop.student.events.index') }}"
                                        class="rounded-lg border border-[color:var(--shop-border-soft)] bg-white px-4 py-2.5 text-sm font-semibold text-[color:var(--shop-text)] transition hover:bg-[color:var(--shop-surface)]"
                                    >
                                        {{ __('shop::app.layout.nav.student-events') }}
                                    </a>
                                </div>

                                <div class="space-y-1 rounded-xl bg-white p-3 w-full text-center">
                                    <p class="text-sm font-bold text-[color:var(--shop-accent)]">
                                        {{ $student->name ?? '' }}
                                    </p>

                                    @if ($student->registration_number)
                                        <p class="text-xs text-[color:var(--shop-text-muted)]">
                                            {{ __('shop::app.layout.nav.student-registration') }}: {{ $student->registration_number }}
                                        </p>
                                    @endif

                                    @if ($student->major)
                                        <p class="text-xs text-[color:var(--shop-text-muted)]">
                                            {{ __('shop::app.student.account.fields.major') }}: {{ $student->major }}
                                        </p>
                                    @endif
                                </div>

                                <form
                                    method="post"
                                    action="{{ route('student.logout') }}"
                                    class="mt-4"
                                >
                                    @csrf
                                    <button
                                        type="submit"
                                        class="w-full rounded-lg bg-[color:var(--shop-primary)] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[color:var(--shop-primary-hover)]"
                                    >
                                        {{ __('shop::app.layout.nav.student-logout') }}
                                    </button>
                                </form>
                            @else
                                <a
                                    href="{{ $studentLoginUrl }}"
                                    class="w-full block rounded-lg bg-[color:var(--shop-primary)] px-4 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-[color:var(--shop-primary-hover)]"
                                >
                                    {{ __('shop::app.layout.nav.student-login') }}
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <form
        action="{{ route('shop.events.index') }}"
        class="mx-auto flex w-full max-w-xl justify-center"
        method="GET"
        role="search"
    >
        <label
            for="portal-search-mobile"
            class="sr-only"
        >
            {{ __('shop::app.components.layouts.header.mobile.search') }}
        </label>

        <div class="relative w-full">
            <div class="icon-search pointer-events-none absolute top-3 flex items-center text-2xl max-md:text-xl max-sm:top-2.5 ltr:left-3 rtl:right-3"></div>

            <input
                id="portal-search-mobile"
                type="text"
                name="query"
                value="{{ request('query') }}"
                class="block w-full rounded-xl border border-[#E3E3E3] px-11 py-3.5 text-sm font-medium text-gray-900 max-md:rounded-lg max-md:px-10 max-md:py-3 max-md:font-normal max-sm:text-xs"
                placeholder="{{ __('shop::app.components.layouts.header.mobile.search-text') }}"
                autocomplete="off"
            >
        </div>
    </form>
</div>

{!! view_render_event('shop.components.layouts.header.mobile.after') !!}
