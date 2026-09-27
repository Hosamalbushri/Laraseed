{!! view_render_event('shop.layout.footer.before') !!}

@php
    use Illuminate\Support\Facades\Schema;
    use Webkul\Shop\Repositories\ShopThemeCustomizationRepository;

    $portalFooter = null;
    if (Schema::hasTable('shop_theme_customizations')) {
        $portalFooter = app(ShopThemeCustomizationRepository::class)
            ->getActivePortalFooter(config('shop.storefront_theme_code', 'default'));
    }

    $footerSections = config('shop.footer_sections', []);
@endphp

@if ($portalFooter && is_array($portalFooter->options) && ($portalFooter->options['enabled'] ?? true))
    <x-shop::layouts.footer.portal :options="$portalFooter->options" />
@else

<footer class="mt-9 bg-lightOrange max-sm:mt-10">
    <div class="flex justify-between gap-x-6 gap-y-8 p-[60px] max-1060:flex-col-reverse max-md:gap-5 max-md:p-8 max-sm:px-4 max-sm:py-5">
        <div class="flex flex-wrap items-start gap-24 max-1180:gap-6 max-1060:hidden">
            @foreach ($footerSections as $section)
                <ul class="grid gap-5 text-sm">
                    @foreach ($section as $link)
                        <x-shop::layouts.footer.link-item
                            :link="$link"
                            link-class="font-medium text-navyBlue hover:opacity-80"
                        />
                    @endforeach
                </ul>
            @endforeach
        </div>

        <x-shop::accordion
            :is-active="false"
            class="hidden !w-full rounded-xl !border-2 !border-[#e9decc] max-1060:block max-sm:rounded-lg"
        >
            <x-slot:header class="rounded-t-lg bg-[#F1EADF] font-medium max-md:p-2.5 max-sm:px-3 max-sm:py-2 max-sm:text-sm">
                @lang('shop::app.components.layouts.footer.footer-content')
            </x-slot>

            <x-slot:content class="flex justify-between !bg-transparent !p-4">
                @foreach ($footerSections as $section)
                    <ul class="grid gap-5 text-sm">
                        @foreach ($section as $link)
                            <x-shop::layouts.footer.link-item
                                :link="$link"
                                link-class="text-sm font-medium max-sm:text-xs text-navyBlue hover:opacity-80"
                            />
                        @endforeach
                    </ul>
                @endforeach
            </x-slot>
        </x-shop::accordion>
    </div>

    <div class="flex justify-between bg-[#F1EADF] px-[60px] py-3.5 max-md:justify-center max-sm:px-5">
        {!! view_render_event('shop.layout.footer.footer_text.before') !!}

        <p class="text-sm text-zinc-600 max-md:text-center">
            @lang('shop::app.components.layouts.footer.footer-text', ['current_year' => date('Y')])
        </p>

        {!! view_render_event('shop.layout.footer.footer_text.after') !!}
    </div>
</footer>

@endif

{!! view_render_event('shop.layout.footer.after') !!}
