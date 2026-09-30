@php
    $headerItems = $headerItems ?? (app()->bound(\Webkul\Web\Contracts\NavigationRegistryContract::class)
        ? app(\Webkul\Web\Contracts\NavigationRegistryContract::class)->getItems('header')
        : collect());
    $navigationLabels = $navigationLabels ?? app(\Webkul\Web\Navigation\NavigationLabelResolver::class);
    $currentLocale = $siteDefinition->locale ?? (isset($webContext) ? $webContext->locale() : app()->getLocale());
@endphp

<header class="w-full bg-white text-slate-950 border-b border-slate-200" data-website-header>
    <div class="mx-auto flex min-h-16 w-full max-w-content items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8">
        {{-- Brand / Logo --}}
        <a href="{{ url('/') }}" class="flex min-w-0 items-center gap-3 rounded-lg text-slate-950 no-underline hover:text-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" aria-label="{{ $siteDefinition->name }}" data-website-header-brand>
            @if ($siteDefinition->logoUrl)
                <img src="{{ $siteDefinition->logoUrl }}" alt="{{ $siteDefinition->logoAlt }}" class="h-9 w-auto max-w-32 shrink-0 object-contain sm:max-w-40" data-website-header-logo>
            @endif
            <span class="truncate text-base font-bold sm:text-lg">{{ $siteDefinition->name }}</span>
        </a>

        {{-- Desktop Navigation --}}
        @if ($headerItems->isNotEmpty())
            <nav class="hidden min-w-0 flex-1 items-center justify-center md:flex" aria-label="{{ trans('website::app.header.primary_navigation') }}">
                <ul class="flex flex-wrap items-center justify-center gap-1" data-website-desktop-navigation>
                    @foreach ($headerItems as $item)
                        @php
                            $isActive = $item->isActive(request());
                        @endphp
                        <li>
                            <a class="inline-flex min-h-10 items-center rounded-lg px-3 py-2 text-sm font-semibold no-underline transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 {{ $isActive ? 'bg-blue-50 text-blue-800 underline decoration-2 underline-offset-8' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}" href="{{ $item->url }}" @if ($item->target) target="{{ $item->target }}" @endif @if ($item->target === '_blank') rel="noopener noreferrer" @endif @if ($isActive) aria-current="page" @endif>{{ $navigationLabels->resolve($item->title) }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif

        {{-- Desktop Locale Switcher & Mobile Trigger --}}
        <div class="flex shrink-0 items-center gap-2">
            <div class="hidden items-center rounded-lg border border-slate-200 bg-slate-100 p-1 sm:flex" role="group" aria-label="{{ trans('website::app.header.language_switcher') }}" data-website-locale-desktop>
                @foreach (['en', 'ar'] as $localeCode)
                    @php
                        $localeIsActive = $currentLocale === $localeCode;
                        $switchLabelKey = $localeCode === 'en' ? 'switch_to_english' : 'switch_to_arabic';
                    @endphp
                    @if ($localeIsActive)
                        <span class="rounded-md bg-white px-2.5 py-1 text-xs font-bold text-blue-800 shadow-sm" aria-current="true" lang="{{ $localeCode }}" dir="{{ $localeCode === 'ar' ? 'rtl' : 'ltr' }}">{{ trans('website::app.header.locale_'.$localeCode) }}</span>
                    @else
                        <a href="{{ route('web.locale.switch', ['code' => $localeCode], false) }}" class="rounded-md px-2.5 py-1 text-xs font-semibold text-slate-600 no-underline hover:bg-white hover:text-slate-950 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-600" lang="{{ $localeCode }}" dir="{{ $localeCode === 'ar' ? 'rtl' : 'ltr' }}" hreflang="{{ $localeCode }}" aria-label="{{ trans('website::app.header.'.$switchLabelKey) }}">{{ trans('website::app.header.locale_'.$localeCode) }}</a>
                    @endif
                @endforeach
            </div>

            {{-- Mobile Drawer Trigger --}}
            <button
                type="button"
                id="website-mobile-menu-trigger"
                class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-800 hover:bg-slate-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 md:hidden"
                data-web-drawer-trigger="website-mobile-drawer"
                aria-haspopup="dialog"
                aria-expanded="false"
                aria-controls="website-mobile-drawer-dialog"
                aria-label="{{ trans('website::app.header.menu_toggle') }}"
            >
                <svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                <span class="hidden sm:inline">{{ trans('website::app.header.menu_label') }}</span>
            </button>
        </div>
    </div>

    {{-- Accessible Mobile Navigation Drawer --}}
    <x-web::drawer id="website-mobile-drawer" placement="end">
        <x-slot:header>
            <div class="flex items-center gap-2">
                @if ($siteDefinition->logoUrl)
                    <img src="{{ $siteDefinition->logoUrl }}" alt="{{ $siteDefinition->logoAlt }}" class="h-7 w-auto max-w-24 shrink-0 object-contain">
                @endif
                <span class="truncate font-bold text-slate-900 text-sm sm:text-base">{{ $siteDefinition->name }}</span>
            </div>
        </x-slot:header>

        <div class="flex flex-col gap-4 py-2">
            @if ($headerItems->isNotEmpty())
                <nav aria-label="{{ trans('website::app.header.mobile_navigation') }}" data-website-mobile-navigation>
                    <ul class="flex flex-col gap-1">
                        @foreach ($headerItems as $item)
                            @php
                                $isActive = $item->isActive(request());
                            @endphp
                            <li>
                                <a class="flex min-h-11 items-center rounded-lg px-3 py-2 text-sm font-semibold no-underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 {{ $isActive ? 'bg-blue-50 text-blue-800 underline' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-950' }}" href="{{ $item->url }}" @if ($item->target) target="{{ $item->target }}" @endif @if ($item->target === '_blank') rel="noopener noreferrer" @endif @if ($isActive) aria-current="page" @endif>{{ $navigationLabels->resolve($item->title) }}</a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endif

            <div class="flex flex-wrap items-center gap-3 border-t border-slate-200 pt-4" role="group" aria-label="{{ trans('website::app.header.language_switcher') }}" data-website-locale-mobile>
                <span class="text-sm font-semibold text-slate-600">{{ trans('website::app.header.language_label') }}</span>
                <div class="flex items-center rounded-lg border border-slate-200 bg-slate-100 p-1">
                    @foreach (['en', 'ar'] as $localeCode)
                        @php
                            $localeIsActive = $currentLocale === $localeCode;
                        @endphp
                        @if ($localeIsActive)
                            <span class="rounded-md bg-white px-3 py-1.5 text-sm font-bold text-blue-800 shadow-sm" aria-current="true" lang="{{ $localeCode }}" dir="{{ $localeCode === 'ar' ? 'rtl' : 'ltr' }}">{{ trans('website::app.header.locale_'.$localeCode) }}</span>
                        @else
                            <a href="{{ route('web.locale.switch', ['code' => $localeCode], false) }}" class="rounded-md px-3 py-1.5 text-sm font-semibold text-slate-600 no-underline hover:bg-white hover:text-slate-950 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-600" lang="{{ $localeCode }}" dir="{{ $localeCode === 'ar' ? 'rtl' : 'ltr' }}" hreflang="{{ $localeCode }}">{{ trans('website::app.header.locale_'.$localeCode) }}</a>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </x-web::drawer>
</header>
