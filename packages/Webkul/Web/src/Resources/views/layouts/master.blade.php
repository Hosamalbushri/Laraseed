@inject('seoMetadata', 'Webkul\Web\Contracts\SeoMetadataContract')
<!DOCTYPE html>
<html lang="{{ $webContext->locale() }}" dir="{{ $webContext->direction() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    {!! $seoMetadata->renderHeadHtml() !!}
    @stack('styles')
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased font-sans">
    <div id="app" class="flex flex-col min-h-screen">
        <header class="w-full bg-white border-b border-gray-200">
            @yield('header')
        </header>

        <main class="flex-1">
            @yield('content')
        </main>

        <footer class="w-full bg-gray-100 border-t border-gray-200 py-6">
            @yield('footer')
        </footer>
    </div>
    @stack('scripts')
</body>
</html>
