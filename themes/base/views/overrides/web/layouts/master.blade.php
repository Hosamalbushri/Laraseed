@inject('seoMetadata', 'Webkul\Web\Contracts\SeoMetadataContract')
@inject('navigation', 'Webkul\Web\Contracts\NavigationRegistryContract')
@inject('navigationLabels', 'Webkul\Web\Navigation\NavigationLabelResolver')
<!DOCTYPE html>
<html lang="{{ $webContext->locale() }}" dir="{{ $webContext->direction() }}" data-theme="base">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    {!! $seoMetadata->renderHeadHtml() !!}
    @if (! empty($webFaviconUrl))
        <link rel="icon" href="{{ $webFaviconUrl }}">
    @endif
    @vite('assets/css/theme.css', 'themes/base/build')
    @stack('styles')
</head>
<body class="web-document">
    <a class="web-skip-link" href="#web-main">{{ trans('web::app.accessibility.skip_to_content') }}</a>

    <div id="app" class="web-shell">
        <header class="web-site-header">
            @hasSection('header')
                @yield('header')
            @else
                @php($headerItems = $navigation->getItems('header'))
                @php($secondaryItems = $navigation->getItems('secondary'))
                @php($mobileItems = $navigation->getItems('mobile'))

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

                    @if ($mobileItems->isNotEmpty())
                        <nav class="web-navigation web-navigation--mobile" aria-label="{{ trans('web::app.navigation.mobile') }}">
                            <ul class="web-navigation__list">
                                @foreach ($mobileItems as $item)
                                    @php($isActive = $item->isActive(request()))
                                    <li class="web-navigation__item"><a class="web-navigation__link{{ $isActive ? ' is-active' : '' }}" href="{{ $item->url }}" @if ($isActive) aria-current="page" @endif>{{ $navigationLabels->resolve($item->title) }}</a></li>
                                @endforeach
                            </ul>
                        </nav>
                    @endif
                </div>

                @if ($secondaryItems->isNotEmpty())
                    <nav class="web-navigation web-navigation--secondary" aria-label="{{ trans('web::app.navigation.secondary') }}">
                        <ul class="web-container web-navigation__list">
                            @foreach ($secondaryItems as $item)
                                @php($isActive = $item->isActive(request()))
                                <li class="web-navigation__item"><a class="web-navigation__link{{ $isActive ? ' is-active' : '' }}" href="{{ $item->url }}" @if ($isActive) aria-current="page" @endif>{{ $navigationLabels->resolve($item->title) }}</a></li>
                            @endforeach
                        </ul>
                    </nav>
                @endif
            @endif
        </header>

        <main id="web-main" class="web-main" tabindex="-1">
            @yield('content')
        </main>

        <footer class="web-site-footer">
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

    @vite('../../packages/Webkul/Web/src/Resources/assets/js/web-interactions.js', 'themes/base/build')
    @stack('scripts')
</body>
</html>
