<!DOCTYPE html>

<html
    lang="{{ app()->getLocale() }}"
    dir="{{ in_array(app()->getLocale(), ['fa', 'ar']) ? 'rtl' : 'ltr' }}"
>

<head>
    <title>{{ $title ?? '' }}</title>

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
        name="currency-code"
        {{-- content="{{ core()->getCurrentCurrencyCode() }}" --}}
    >

    @stack('meta')

    {{
        vite()->set(['src/Resources/assets/css/app.css', 'src/Resources/assets/js/app.js'])
    }}

    @if ($favicon = core()->getConfigData('general.design.admin_logo.favicon'))
        <link
            type="image/x-icon"
            href="{{ Storage::url($favicon) }}"
            rel="shortcut icon"
            sizes="16x16"
        >
    @else
        <link
            type="image/x-icon"
            href="{{ vite()->asset('images/favicon.ico') }}"
            rel="shortcut icon"
            sizes="16x16"
        />
    @endif

    @php
        $menuBrand = core()->getConfigData('general.settings.menu_color.brand_color') ?? '#0E90D9';
        $shopPrimary = core()->getConfigData('general.store.shop.primary_color')
            ?: core()->getConfigData('general.design.shop.primary_color');
        $shopAccent = core()->getConfigData('general.store.shop.accent_color')
            ?: core()->getConfigData('general.design.shop.accent_color');
        $primaryOk = is_string($shopPrimary) && preg_match('/^#[0-9A-Fa-f]{6}$/', $shopPrimary);
        $accentOk = is_string($shopAccent) && preg_match('/^#[0-9A-Fa-f]{6}$/', $shopAccent);
        $brandColor = $primaryOk ? $shopPrimary : $menuBrand;
        $accentColor = $accentOk ? $shopAccent : $brandColor;
    @endphp

    @stack('styles')

    <style>
        :root,
        body {
            font-family: 'Cairo', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, Arial, 'Noto Sans', 'Apple Color Emoji', 'Segoe UI Emoji';
        }
    </style>

    <style>
        :root {
            --brand-color: {{ $brandColor }};
            --shop-accent: {{ $accentColor }};
        }

        {!! core()->getConfigData('general.content.custom_scripts.custom_css') !!}
    </style>

    {!! view_render_event('admin.layout.head') !!}
</head>

<body>
    {!! view_render_event('admin.layout.body.before') !!}

    <div id="app">
        <!-- Flash Message Blade Component -->
        <x-admin::flash-group />

        {!! view_render_event('admin.layout.content.before') !!}

        <!-- Page Content Blade Component -->
        {{ $slot }}

        {!! view_render_event('admin.layout.content.after') !!}
    </div>

    {!! view_render_event('admin.layout.body.after') !!}

    @stack('scripts')

    {!! view_render_event('admin.layout.vue-app-mount.before') !!}

    <script>
        /**
         * Load event, the purpose of using the event is to mount the application
         * after all of our `Vue` components which is present in blade file have
         * been registered in the app. No matter what `app.mount()` should be
         * called in the last.
         */
        window.addEventListener("load", function(event) {
            app.mount("#app");
        });
    </script>

    {!! view_render_event('admin.layout.vue-app-mount.after') !!}

    <script type="text/javascript">
        {!! core()->getConfigData('general.content.custom_scripts.custom_javascript') !!}
    </script>
</body>

</html>
