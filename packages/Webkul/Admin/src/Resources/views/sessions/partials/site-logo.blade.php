@php
    $siteLogo = core()->getConfigData('general.store.shop.logo_image')
        ?: core()->getConfigData('general.design.shop.logo_image');
    $adminLogo = core()->getConfigData('general.design.admin_logo.logo_image');
@endphp
@if ($siteLogo)
    <img
        class="max-h-14 max-w-[240px] w-auto object-contain"
        src="{{ Storage::url($siteLogo) }}"
        alt="{{ config('app.name') }}"
    >
@elseif ($adminLogo)
    <img
        class="h-10 max-w-[200px] w-auto object-contain"
        src="{{ Storage::url($adminLogo) }}"
        alt="{{ config('app.name') }}"
    >
@else
    <img
        class="max-h-12 w-auto max-w-full object-contain"
        src="{{ vite()->asset('images/logo.svg') }}"
        alt="{{ config('app.name') }}"
    >
@endif
