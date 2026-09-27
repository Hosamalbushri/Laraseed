{!! view_render_event('shop.layout.header.before') !!}

<header class="sticky top-0 z-50 bg-white shadow-sm max-lg:shadow-none">
    <div class="hidden lg:block">
        <x-shop::layouts.header.desktop />
    </div>

    <div class="lg:hidden">
        <x-shop::layouts.header.mobile />
    </div>
</header>

{!! view_render_event('shop.layout.header.after') !!}
