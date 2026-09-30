@php
    $footerItems = $footerItems ?? (app()->bound(\Webkul\Web\Contracts\NavigationRegistryContract::class)
        ? app(\Webkul\Web\Contracts\NavigationRegistryContract::class)->getItems('footer')
        : collect());
    $navigationLabels = $navigationLabels ?? app(\Webkul\Web\Navigation\NavigationLabelResolver::class);
    $currentLocale = $siteDefinition->locale ?? (isset($webContext) ? $webContext->locale() : app()->getLocale());
@endphp

<div class="mx-auto w-full max-w-content px-4 py-10 sm:px-6 lg:px-8" data-website-footer>
    <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
        <div class="min-w-0" data-website-footer-identity>
            <div class="flex min-w-0 items-center gap-3">
                @if ($siteDefinition->logoUrl)
                    <img src="{{ $siteDefinition->logoUrl }}" alt="{{ $siteDefinition->logoAlt }}" class="h-10 w-auto max-w-36 shrink-0 object-contain" loading="lazy">
                @endif
                <div class="min-w-0">
                    <p class="break-words text-lg font-bold text-slate-950">{{ $siteDefinition->name }}</p>
                    @if ($siteDefinition->tagline)
                        <p class="mt-1 break-words text-sm text-slate-600">{{ $siteDefinition->tagline }}</p>
                    @endif
                </div>
            </div>
            @if ($siteDefinition->description)
                <p class="mt-4 max-w-prose break-words text-sm leading-6 text-slate-600">{{ $siteDefinition->description }}</p>
            @endif
        </div>

        @if ($footerItems->isNotEmpty())
            <div data-website-footer-navigation>
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-900">{{ trans('website::app.footer.navigation_heading') }}</h2>
                <nav class="mt-4" aria-label="{{ trans('website::app.footer.footer_navigation') }}">
                    <ul class="flex flex-col items-start gap-2">
                        @foreach ($footerItems as $item)
                            @php
                                $isActive = $item->isActive(request());
                            @endphp
                            <li><a class="inline-flex min-h-10 items-center rounded-md px-2 text-sm font-semibold no-underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 {{ $isActive ? 'text-blue-800 underline decoration-2 underline-offset-4' : 'text-slate-600 hover:text-slate-950' }}" href="{{ $item->url }}" @if ($item->target) target="{{ $item->target }}" @endif @if ($item->target === '_blank') rel="noopener noreferrer" @endif @if ($isActive) aria-current="page" @endif>{{ $navigationLabels->resolve($item->title) }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            </div>
        @endif

        @if ($siteDefinition->hasContact())
            <div class="min-w-0" data-website-footer-contact>
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-900">{{ trans('website::app.footer.contact_heading') }}</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    @if ($siteDefinition->address)
                        <div><dt class="font-semibold text-slate-800">{{ trans('website::app.contact.address') }}</dt><dd class="mt-1 break-words text-slate-600">{{ $siteDefinition->address }}</dd></div>
                    @endif
                    @if ($siteDefinition->email)
                        <div><dt class="font-semibold text-slate-800">{{ trans('website::app.contact.email') }}</dt><dd class="mt-1 min-w-0"><a href="mailto:{{ $siteDefinition->email }}" class="break-all text-blue-700 underline-offset-4 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-600">{{ $siteDefinition->email }}</a></dd></div>
                    @endif
                    @if ($siteDefinition->phone)
                        <div><dt class="font-semibold text-slate-800">{{ trans('website::app.contact.phone') }}</dt><dd class="mt-1"><a href="tel:{{ $siteDefinition->phoneHref() }}" class="text-blue-700 underline-offset-4 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-600" dir="ltr">{{ $siteDefinition->phone }}</a></dd></div>
                    @endif
                    @if ($siteDefinition->officeHours)
                        <div><dt class="font-semibold text-slate-800">{{ trans('website::app.contact.office_hours') }}</dt><dd class="mt-1 break-words text-slate-600">{{ $siteDefinition->officeHours }}</dd></div>
                    @endif
                </dl>
            </div>
        @endif
    </div>

    <div class="mt-8 flex flex-col gap-4 border-t border-slate-300 pt-6 text-xs text-slate-600 sm:flex-row sm:items-center sm:justify-between">
        <p class="break-words">&copy; {{ date('Y') }} {{ $siteDefinition->name }}. {{ trans('web::app.footer.copyright') }}</p>
        <div class="flex items-center gap-2" role="group" aria-label="{{ trans('website::app.header.language_switcher') }}" data-website-footer-locale>
            @foreach (['en', 'ar'] as $localeCode)
                @php
                    $localeIsActive = $currentLocale === $localeCode;
                @endphp
                @if ($localeIsActive)
                    <span class="font-bold text-blue-800 underline" aria-current="true" lang="{{ $localeCode }}" dir="{{ $localeCode === 'ar' ? 'rtl' : 'ltr' }}">{{ trans('website::app.header.locale_'.$localeCode) }}</span>
                @else
                    <a href="{{ route('web.locale.switch', ['code' => $localeCode], false) }}" class="font-semibold text-slate-600 no-underline hover:text-slate-950 hover:underline" lang="{{ $localeCode }}" dir="{{ $localeCode === 'ar' ? 'rtl' : 'ltr' }}" hreflang="{{ $localeCode }}">{{ trans('website::app.header.locale_'.$localeCode) }}</a>
                @endif
            @endforeach
        </div>
    </div>
</div>
