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
    @vite('assets/css/theme.css', 'themes/base/build')
    @stack('styles')
</head>
<body class="web-document">
    <a class="web-skip-link" href="#web-main">{{ trans('web::app.accessibility.skip_to_content') }}</a>

    <div class="web-shell">
        @php($headerItems = $navigation->getItems('header'))
        @php($secondaryItems = $navigation->getItems('secondary'))
        @php($mobileItems = $navigation->getItems('mobile'))

        <header class="web-site-header">
            <div class="web-container web-site-header__inner">
                @hasSection('header')
                    <div class="web-site-header__content">@yield('header')</div>
                @endif

                @if ($headerItems->isNotEmpty())
                    <nav class="web-navigation web-navigation--header" aria-label="{{ trans('web::app.navigation.primary') }}">
                        <ul class="web-navigation__list">
                            @foreach ($headerItems as $item)
                                <li class="web-navigation__item">
                                    <a class="web-navigation__link" href="{{ $item->url }}" @if ($item->target) target="{{ $item->target }}" @endif @if ($item->target === '_blank') rel="noopener noreferrer" @endif>{{ $navigationLabels->resolve($item->title) }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                @endif

                @if ($mobileItems->isNotEmpty())
                    <nav class="web-navigation web-navigation--mobile" aria-label="{{ trans('web::app.navigation.mobile') }}">
                        <ul class="web-navigation__list">
                            @foreach ($mobileItems as $item)
                                <li class="web-navigation__item"><a class="web-navigation__link" href="{{ $item->url }}">{{ $navigationLabels->resolve($item->title) }}</a></li>
                            @endforeach
                        </ul>
                    </nav>
                @endif
            </div>

            @if ($secondaryItems->isNotEmpty())
                <nav class="web-navigation web-navigation--secondary" aria-label="{{ trans('web::app.navigation.secondary') }}">
                    <ul class="web-container web-navigation__list">
                        @foreach ($secondaryItems as $item)
                            <li class="web-navigation__item"><a class="web-navigation__link" href="{{ $item->url }}">{{ $navigationLabels->resolve($item->title) }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endif
        </header>

        <main id="web-main" class="web-main" tabindex="-1">
            @yield('content')
        </main>

        @php($footerItems = $navigation->getItems('footer'))
        <footer class="web-site-footer">
            <div class="web-container web-site-footer__inner">
                @hasSection('footer')
                    <div class="web-site-footer__content">@yield('footer')</div>
                @endif

                @if ($footerItems->isNotEmpty())
                    <nav class="web-navigation web-navigation--footer" aria-label="{{ trans('web::app.navigation.footer') }}">
                        <ul class="web-navigation__list">
                            @foreach ($footerItems as $item)
                                <li class="web-navigation__item"><a class="web-navigation__link" href="{{ $item->url }}">{{ $navigationLabels->resolve($item->title) }}</a></li>
                            @endforeach
                        </ul>
                    </nav>
                @endif
            </div>
        </footer>
    </div>

    @vite('../../packages/Webkul/Web/src/Resources/assets/js/web-interactions.js', 'themes/base/build')
    @stack('scripts')
</body>
</html>
