@inject('seoMetadata', 'Webkul\Web\Contracts\SeoMetadataContract')
@inject('navigation', 'Webkul\Web\Contracts\NavigationRegistryContract')
@inject('navigationLabels', 'Webkul\Web\Navigation\NavigationLabelResolver')
<!DOCTYPE html>
<html lang="{{ $webContext->locale() }}" dir="{{ $webContext->direction() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    {!! $seoMetadata->renderHeadHtml() !!}
    @if (! empty($webFaviconUrl))
        <link rel="icon" href="{{ $webFaviconUrl }}">
    @endif
    @stack('styles')
</head>
<body class="web-document min-h-screen bg-gray-50 text-gray-900 antialiased font-sans">
    <a class="web-skip-link" href="#web-main">{{ trans('web::app.accessibility.skip_to_content') }}</a>

    <div id="app" class="web-shell flex flex-col min-h-screen">
        <header class="web-site-header w-full bg-white border-b border-gray-200">
            @hasSection('header')
                @yield('header')
            @else
                @php($headerItems = $navigation->getItems('header'))
                <div class="web-container web-site-header__inner">
                    <div class="web-site-header__brand">
                        <a class="web-site-header__brand-link" href="{{ route('web.home', [], false) }}">{{ config('app.name', 'CampusHub') }}</a>
                    </div>

                    @if ($headerItems->isNotEmpty())
                        <nav class="web-navigation web-navigation--header" aria-label="{{ trans('web::app.navigation.primary') }}">
                            <ul class="web-navigation__list">
                                @foreach ($headerItems as $item)
                                    @php($isActive = $item->isActive(request()))
                                    <li class="web-navigation__item">
                                        <a class="web-navigation__link{{ $isActive ? ' is-active' : '' }}" href="{{ $item->url }}" @if ($item->target) target="{{ $item->target }}" @endif @if ($item->target === '_blank') rel="noopener noreferrer" @endif @if ($isActive) aria-current="page" @endif>{{ $navigationLabels->resolve($item->title) }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </nav>
                    @endif
                </div>
            @endif
        </header>

        <main id="web-main" class="web-main flex-1" tabindex="-1">
            @yield('content')
        </main>

        <footer class="web-site-footer w-full bg-gray-100 border-t border-gray-200 py-6">
            @hasSection('footer')
                @yield('footer')
            @else
                @php($footerItems = $navigation->getItems('footer'))
                <div class="web-container web-site-footer__inner">
                    <div class="web-site-footer__content">
                        <p class="web-site-footer__copyright">&copy; {{ date('Y') }} {{ config('app.name', 'CampusHub') }}. {{ trans('web::app.footer.copyright') }}</p>
                    </div>

                    @if ($footerItems->isNotEmpty())
                        <nav class="web-navigation web-navigation--footer" aria-label="{{ trans('web::app.navigation.footer') }}">
                            <ul class="web-navigation__list">
                                @foreach ($footerItems as $item)
                                    @php($isActive = $item->isActive(request()))
                                    <li class="web-navigation__item"><a class="web-navigation__link{{ $isActive ? ' is-active' : '' }}" href="{{ $item->url }}" @if ($isActive) aria-current="page" @endif>{{ $navigationLabels->resolve($item->title) }}</a></li>
                                @endforeach
                            </ul>
                        </nav>
                    @endif
                </div>
            @endif
        </footer>
    </div>
    @stack('scripts')
</body>
</html>
