@props([
    'hasHeader' => true,
    'hasFeature' => true,
    'hasFooter' => true,
    'title' => null,
])

<!DOCTYPE html>

<html
    lang="{{ app()->getLocale() }}"
    dir="{{ in_array(app()->getLocale(), ['fa', 'ar']) ? 'rtl' : 'ltr' }}"
>
    <head>
        {!! view_render_event('shop.layout.head.before') !!}

        <title>{{ $title ?? __('shop::app.meta.title') }}</title>

        <meta charset="UTF-8">

        <meta
            http-equiv="X-UA-Compatible"
            content="IE=edge"
        >
        <meta
            http-equiv="content-language"
            content="{{ app()->getLocale() }}"
        >

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1"
        >
        <meta
            name="base-url"
            content="{{ url()->to('/') }}"
        >
        <meta
            name="csrf-token"
            content="{{ csrf_token() }}"
        >

        @if ($favicon = core()->getConfigData('general.store.shop.favicon') ?: core()->getConfigData('general.design.shop.favicon'))
            <link
                type="image/x-icon"
                href="{{ \Illuminate\Support\Facades\Storage::url($favicon) }}"
                rel="shortcut icon"
                sizes="16x16"
            >
        @else
            <link
                type="image/x-icon"
                href="{{ vite()->asset('images/favicon.ico') }}"
                rel="shortcut icon"
                sizes="16x16"
            >
        @endif

        @stack('meta')

        {{
            vite()->set(
                ['src/Resources/assets/css/app.css', 'src/Resources/assets/js/app.js'],
                'shop'
            )
        }}

        @php
            $shopPrimary = core()->getConfigData('general.store.shop.primary_color')
                ?: core()->getConfigData('general.design.shop.primary_color')
                ?: '#0284c7';
            $shopAccent = core()->getConfigData('general.store.shop.accent_color')
                ?: core()->getConfigData('general.design.shop.accent_color')
                ?: '#0369a1';
            $shopIconColor = core()->getConfigData('general.store.shop.icon_color')
                ?: core()->getConfigData('general.design.shop.icon_color')
                ?: $shopAccent;
            $shopBadgeColor = core()->getConfigData('general.store.shop.badge_color')
                ?: core()->getConfigData('general.design.shop.badge_color')
                ?: $shopPrimary;
            if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $shopPrimary)) {
                $shopPrimary = '#0284c7';
            }
            if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $shopAccent)) {
                $shopAccent = '#0369a1';
            }
            if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $shopIconColor)) {
                $shopIconColor = $shopAccent;
            }
            if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $shopBadgeColor)) {
                $shopBadgeColor = $shopPrimary;
            }
        @endphp
        <style id="shop-theme-colors">
            :root {
                --shop-font-sans: 'Cairo', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, Arial, 'Noto Sans', 'Apple Color Emoji', 'Segoe UI Emoji';
                --shop-primary: {{ $shopPrimary }};
                --shop-accent: {{ $shopAccent }};
                --shop-icon-color: {{ $shopIconColor }};
                --shop-badge-color: {{ $shopBadgeColor }};
                --shop-primary-hover: color-mix(in srgb, var(--shop-primary) 88%, black);
                --shop-accent-hover: color-mix(in srgb, var(--shop-accent) 82%, black);
                --shop-ring: color-mix(in srgb, var(--shop-primary) 55%, transparent);
                --shop-surface: color-mix(in srgb, var(--shop-primary) 12%, white);
                --shop-surface-strong: color-mix(in srgb, var(--shop-primary) 22%, white);
                --shop-border-soft: color-mix(in srgb, var(--shop-primary) 28%, white);
                --shop-border-hover: color-mix(in srgb, var(--shop-primary) 38%, white);
                --shop-gradient-from: color-mix(in srgb, var(--shop-primary) 16%, white);
                --shop-gradient-mid: color-mix(in srgb, var(--shop-primary) 6%, white);
                --shop-placeholder: color-mix(in srgb, var(--shop-primary) 42%, #64748b);
                --shop-badge-ring: color-mix(in srgb, var(--shop-primary) 14%, transparent);
            }
        </style>

        @pushOnce('styles', 'shop-cairo-font')
            <style>
                :root { font-family: var(--shop-font-sans); }
                body { font-family: var(--shop-font-sans); }
            </style>
        @endpushOnce

        {{-- Styles stack must run after slot + footer: @push from page/footer renders later than <head>. --}}

        {!! view_render_event('shop.layout.head.after') !!}
    </head>

    <body>
        {!! view_render_event('shop.layout.body.before') !!}

        <a
            href="#main"
            class="skip-to-main-content-link"
        >
            {{ __('shop::app.components.layouts.skip-to-content') }}
        </a>

        <x-shop::flash-group />

        @if ($hasHeader)
            <x-shop::layouts.header />
        @endif

        {!! view_render_event('shop.layout.content.before') !!}

        <main
            id="main"
            class="min-h-[40vh] bg-white"
        >
            {{ $slot }}
        </main>

        {!! view_render_event('shop.layout.content.after') !!}

        @if ($hasFeature)
            <x-shop::layouts.services />
        @endif

        @if ($hasFooter)
            <x-shop::layouts.footer />
        @endif

        @stack('styles')

        {!! view_render_event('shop.layout.body.after') !!}

        @stack('scripts')
    </body>
</html>
