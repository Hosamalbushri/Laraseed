@php
    use Webkul\Shop\Models\ThemeCustomization;

    $metaTitle = $homeSeo['meta_title'] ?? __('shop::app.home.seo.meta-title');
    $metaDescription = $homeSeo['meta_description'] ?? __('shop::app.home.seo.meta-description');
    $metaKeywords = $homeSeo['meta_keywords'] ?? __('shop::app.home.seo.meta-keywords');
@endphp

@push('meta')
    <meta name="title" content="{{ $metaTitle }}" />
    <meta name="description" content="{{ $metaDescription }}" />
    <meta name="keywords" content="{{ $metaKeywords }}" />
@endpush

<x-shop::layouts :title="$metaTitle">
    @foreach ($customizations as $customization)
        @php
            $data = $customization->options ?? [];
        @endphp

        @switch ($customization->type)
            @case (ThemeCustomization::IMAGE_CAROUSEL)
                <x-shop::carousel
                    :options="$data"
                    :aria-label="trans('shop::app.home.index.image-carousel')"
                />

                @break

            @case (ThemeCustomization::IMMERSIVE_HERO)
                <x-shop::home.immersive-hero
                    :options="$data"
                    :aria-label="__('shop::app.home.index.immersive-hero')"
                />

                @break

            @case (ThemeCustomization::STATIC_CONTENT)
                @if (! empty($data['css']))
                    @push('styles')
                        <style>
                            {{ $data['css'] }}
                        </style>
                    @endpush
                @endif

                @if (! empty($data['view']))
                    @include($data['view'])
                @endif

                @if (! empty($data['html']))
                    {!! $data['html'] !!}
                @endif

                @break

            @case (ThemeCustomization::EVENT_CAROUSEL)
                <x-shop::events.carousel
                    :options="$data"
                    :events="$customization->_events ?? collect()"
                    :subscribed-event-ids="$subscribedEventIds ?? []"
                    :aria-label="trans('shop::app.home.index.events-carousel')"
                />

                @break

            @case (ThemeCustomization::CATEGORY_CAROUSEL)
                <x-shop::home.event-category-carousel
                    :options="$data"
                    :categories="$customization->_categories ?? collect()"
                    :aria-label="trans('shop::app.home.index.categories-carousel')"
                />

                @break

            @case (ThemeCustomization::FOOTER_LINKS)
                <x-shop::home.footer-links
                    :options="$data"
                    :aria-label="trans('shop::app.home.index.footer-links')"
                />

                @break

            @case (ThemeCustomization::SERVICES_CONTENT)
                <x-shop::home.services-strip
                    :options="$data"
                    :aria-label="trans('shop::app.home.index.services-strip')"
                />

                @break

            @case (ThemeCustomization::PRODUCT_CAROUSEL)
                {{-- Intentionally not rendered: product catalog is out of scope for this portal. --}}
                @break

            @case (ThemeCustomization::PORTAL_FOOTER)
                {{-- Global layout footer only; excluded from home loop via HomeController. --}}
                @break

            @default
        @endswitch
    @endforeach
</x-shop::layouts>
