@php
    $opts = is_array($theme->options) ? $theme->options : [];
@endphp

<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.settings.shop-theme.edit.title')
    </x-slot>

    <x-admin::form
        :action="route('admin.settings.shop-theme.update', $theme->id)"
        enctype="multipart/form-data"
        method="POST"
    >
        @method('PUT')

        <div class="flex flex-col gap-4">
            {{-- Same header strip as events edit --}}
            <div
                class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
            >
                <div class="flex flex-col gap-2">
                    <x-admin::breadcrumbs
                        name="settings.shop_theme.edit"
                        :entity="$theme"
                    />

                    <div class="text-xl font-bold dark:text-white">
                        @lang('admin::app.settings.shop-theme.edit.title')
                    </div>
                </div>

                <div class="flex items-center gap-x-2.5">
                    <div class="flex items-center gap-x-2.5">
                        <a
                            href="{{ route('admin.settings.shop-theme.index') }}"
                            class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                        >
                            @lang('admin::app.settings.shop-theme.edit.back')
                        </a>

                        @if (bouncer()->hasPermission('settings.shop_theme.edit'))
                            <button
                                type="submit"
                                class="primary-button"
                            >
                                @lang('admin::app.settings.shop-theme.edit.save-btn')
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Same two-column body as events edit: flex row, left flex-1, right w-[500px] --}}
            <div class="flex gap-2.5 max-xl:flex-wrap">
                {{-- Left: section content in accordion (title in header, same pattern as details column) --}}
                <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
                    <x-admin::accordion class="rounded-lg">
                        <x-slot:header>
                            <div class="flex items-center justify-between">
                                <p class="p-2.5 text-base font-semibold text-gray-800 dark:text-white">
                                    @lang('admin::app.settings.shop-theme.edit.content-panel')
                                </p>
                            </div>
                        </x-slot>

                        <x-slot:content>
                            <div class="flex flex-col gap-2">
                                @includeWhen($theme->type === 'static_content', 'admin::settings.shop-theme.edit.static-content', [
                                    'theme' => $theme,
                                    'opts' => $opts,
                                ])

                                @includeWhen($theme->type === 'image_carousel', 'admin::settings.shop-theme.edit.image-carousel', [
                                    'theme' => $theme,
                                    'opts' => $opts,
                                ])

                                @includeWhen($theme->type === 'event_carousel', 'admin::settings.shop-theme.edit.event-carousel', [
                                    'theme' => $theme,
                                    'opts' => $opts,
                                ])

                                @includeWhen($theme->type === 'category_carousel', 'admin::settings.shop-theme.edit.category-carousel', [
                                    'theme' => $theme,
                                    'opts' => $opts,
                                ])

                                @includeWhen($theme->type === 'footer_links', 'admin::settings.shop-theme.edit.footer-links', [
                                    'theme' => $theme,
                                    'opts' => $opts,
                                ])

                                @includeWhen($theme->type === 'services_content', 'admin::settings.shop-theme.edit.services-content', [
                                    'theme' => $theme,
                                    'opts' => $opts,
                                ])

                                @includeWhen($theme->type === 'immersive_hero', 'admin::settings.shop-theme.edit.immersive-hero', [
                                    'theme' => $theme,
                                    'opts' => $opts,
                                ])

                                @includeWhen($theme->type === 'portal_footer', 'admin::settings.shop-theme.edit.portal-footer', [
                                    'theme' => $theme,
                                    'opts' => $opts,
                                ])

                                @if ($theme->type === 'product_carousel')
                                    <div class="box-shadow rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                                        @lang('admin::app.settings.shop-theme.edit.product-carousel-legacy')
                                    </div>
                                @endif
                            </div>
                        </x-slot>
                    </x-admin::accordion>
                </div>

                {{-- Right: details in accordion (same as events edit sidebar) --}}
                <div class="flex w-[500px] max-w-full flex-col gap-2 max-sm:w-full">
                    <x-admin::accordion class="rounded-lg">
                        <x-slot:header>
                            <div class="flex items-center justify-between">
                                <p class="p-2.5 text-base font-semibold text-gray-800 dark:text-white">
                                    @lang('admin::app.settings.shop-theme.edit.general')
                                </p>
                            </div>
                        </x-slot>

                        <x-slot:content>
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.settings.shop-theme.edit.name')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="name"
                                    rules="required"
                                    :value="$theme->name"
                                    :label="trans('admin::app.settings.shop-theme.edit.name')"
                                />

                                <x-admin::form.control-group.error control-name="name" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.settings.shop-theme.edit.sort-order')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="sort_order"
                                    rules="required|numeric"
                                    :value="$theme->sort_order"
                                    :label="trans('admin::app.settings.shop-theme.edit.sort-order')"
                                />

                                <x-admin::form.control-group.error control-name="sort_order" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.settings.shop-theme.edit.type')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="select"
                                    name="type"
                                    rules="required"
                                    :value="$theme->type"
                                >
                                    <option value="image_carousel">@lang('admin::app.settings.shop-theme.create.type.image-carousel')</option>
                                    <option value="static_content">@lang('admin::app.settings.shop-theme.create.type.static-content')</option>
                                    <option value="event_carousel">@lang('admin::app.settings.shop-theme.create.type.event-carousel')</option>
                                    <option value="category_carousel">@lang('admin::app.settings.shop-theme.create.type.category-carousel')</option>
                                    <option value="footer_links">@lang('admin::app.settings.shop-theme.create.type.footer-links')</option>
                                    <option value="services_content">@lang('admin::app.settings.shop-theme.create.type.services-content')</option>
                                    <option value="immersive_hero">@lang('admin::app.settings.shop-theme.create.type.immersive-hero')</option>
                                    <option value="portal_footer">@lang('admin::app.settings.shop-theme.create.type.portal-footer')</option>
                                    @if ($theme->type === 'product_carousel')
                                        <option value="product_carousel">@lang('admin::app.settings.shop-theme.create.type.product-carousel-legacy')</option>
                                    @endif
                                </x-admin::form.control-group.control>

                                <x-admin::form.control-group.error control-name="type" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.settings.shop-theme.create.theme-code')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="select"
                                    name="theme_code"
                                    rules="required"
                                    :value="$theme->theme_code"
                                >
                                    @foreach (config('shop.theme_definitions', []) as $code => $def)
                                        <option value="{{ $code }}">{{ $def['name'] ?? $code }}</option>
                                    @endforeach
                                </x-admin::form.control-group.control>

                                <x-admin::form.control-group.error control-name="theme_code" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group class="!mb-0">
                                <input
                                    type="hidden"
                                    name="status"
                                    value="0"
                                >

                                <label class="flex cursor-pointer items-center gap-2.5 text-sm text-gray-800 dark:text-gray-200">
                                    <input
                                        type="checkbox"
                                        name="status"
                                        value="1"
                                        class="rounded border-gray-300 text-brandColor focus:ring-brandColor dark:border-gray-600 dark:bg-gray-900"
                                        {{ $theme->status ? 'checked' : '' }}
                                    >

                                    <span>@lang('admin::app.settings.shop-theme.edit.status-active')</span>
                                </label>
                            </x-admin::form.control-group>
                        </x-slot>
                    </x-admin::accordion>
                </div>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts>
