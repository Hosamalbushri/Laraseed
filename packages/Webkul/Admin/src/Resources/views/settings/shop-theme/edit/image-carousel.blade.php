@php
    use Illuminate\Support\Facades\Storage;

    $images = $opts['images'] ?? [];
    $adminInput =
        'w-full rounded border border-gray-200 px-2.5 py-2 text-sm font-normal text-gray-800 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400';
    $adminFile =
        'w-full rounded border border-gray-200 px-2.5 py-2 text-sm font-normal text-gray-800 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400 dark:file:bg-gray-800';
@endphp

<div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <div>
            <p class="text-base font-semibold text-gray-800 dark:text-white">
                @lang('admin::app.settings.shop-theme.edit.carousel-heading')
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                @lang('admin::app.settings.shop-theme.edit.carousel-help')
            </p>
        </div>

        <x-admin::button
            type="button"
            button-type="button"
            class="secondary-button"
            :title="trans('admin::app.settings.shop-theme.edit.carousel-add')"
            data-shop-theme-carousel-add="{{ $theme->id }}"
        />
    </div>

    <div
        id="carousel-rows-{{ $theme->id }}"
        class="flex flex-col gap-4"
    >
        @foreach ($images as $i => $img)
            <div
                class="carousel-row-{{ $theme->id }} grid gap-3 border-b border-gray-100 pb-4 dark:border-gray-800"
                data-carousel-row
            >
                @if (! empty($img['image']))
                    <x-admin::form.control-group class="!mb-0">
                        <div class="flex items-center gap-3">
                            @php
                                $src = $img['image'];
                                $pub = ltrim(str_replace('storage/', '', $src), '/');
                                $preview = Storage::disk('public')->exists($pub) ? Storage::url($pub) : asset('storage/'.$pub);
                            @endphp
                            <img
                                src="{{ $preview }}"
                                alt=""
                                class="h-16 w-28 rounded object-cover"
                            >
                            <input
                                type="hidden"
                                name="options[{{ $i }}][image_path]"
                                value="{{ $img['image'] }}"
                            >
                        </div>
                    </x-admin::form.control-group>
                @endif

                <div class="grid gap-3 sm:grid-cols-2">
                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.label>
                            @lang('admin::app.settings.shop-theme.edit.carousel-link')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="options[{{ $i }}][link]"
                            :value="$img['link'] ?? ''"
                            :label="trans('admin::app.settings.shop-theme.edit.carousel-link')"
                        />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.label>
                            @lang('admin::app.settings.shop-theme.edit.carousel-title')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="options[{{ $i }}][title]"
                            :value="$img['title'] ?? ''"
                            :label="trans('admin::app.settings.shop-theme.edit.carousel-title')"
                        />
                    </x-admin::form.control-group>
                </div>

                <x-admin::form.control-group class="!mb-0">
                    <x-admin::form.control-group.label>
                        @lang('admin::app.settings.shop-theme.edit.carousel-replace')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="file"
                        name="options[{{ $i }}][image]"
                        :label="trans('admin::app.settings.shop-theme.edit.carousel-replace')"
                        accept="image/jpeg,image/png,image/webp,image/gif"
                    />
                </x-admin::form.control-group>

                <x-admin::form.control-group class="!mb-0">
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <input
                            type="checkbox"
                            value="1"
                            data-mark-delete="{{ $theme->id }}"
                            data-image-path="{{ $img['image'] ?? '' }}"
                            class="rounded border-gray-300 text-brandColor focus:ring-brandColor dark:border-gray-600 dark:bg-gray-900"
                        >
                        <span>@lang('admin::app.settings.shop-theme.edit.carousel-remove')</span>
                    </label>
                </x-admin::form.control-group>
            </div>
        @endforeach
    </div>

    {{-- Prototype: markup mirrors control-group + label styling; no v-field (clone-safe) --}}
    <div
        id="carousel-row-proto-{{ $theme->id }}"
        class="hidden"
        hidden
        aria-hidden="true"
    >
        <div
            class="carousel-row-{{ $theme->id }} grid gap-3 border-b border-gray-100 pb-4 dark:border-gray-800"
            data-carousel-row
        >
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="mb-4">
                    <label class="mb-1.5 flex items-center gap-1 text-sm font-normal text-gray-800 dark:text-white">
                        @lang('admin::app.settings.shop-theme.edit.carousel-link')
                    </label>
                    <input
                        type="text"
                        name="options[__INDEX__][link]"
                        class="{{ $adminInput }}"
                    >
                </div>
                <div class="mb-4">
                    <label class="mb-1.5 flex items-center gap-1 text-sm font-normal text-gray-800 dark:text-white">
                        @lang('admin::app.settings.shop-theme.edit.carousel-title')
                    </label>
                    <input
                        type="text"
                        name="options[__INDEX__][title]"
                        class="{{ $adminInput }}"
                    >
                </div>
            </div>

            <div class="mb-4">
                <label class="mb-1.5 flex items-center gap-1 text-sm font-normal text-gray-800 dark:text-white">
                    @lang('admin::app.settings.shop-theme.edit.carousel-image')
                </label>
                <input
                    type="file"
                    name="options[__INDEX__][image]"
                    accept="image/jpeg,image/png,image/webp,image/gif"
                    class="{{ $adminFile }}"
                >
            </div>
        </div>
    </div>
</div>

@pushOnce('scripts', 'shop-theme-carousel-{{ $theme->id }}')
    <script>
        (function () {
            const themeId = {{ $theme->id }};

            function elTarget(e) {
                const t = e.target;
                return t instanceof Element ? t : t && t.parentElement;
            }

            function carouselContainer() {
                return document.getElementById('carousel-rows-' + themeId);
            }

            function carouselPrototypeNode() {
                const holder = document.getElementById('carousel-row-proto-' + themeId);
                return holder ? holder.querySelector('[data-carousel-row]') : null;
            }

            window['shopThemeAddCarouselRow' + themeId] = function () {
                const container = carouselContainer();
                const proto = carouselPrototypeNode();
                if (! container || ! proto) {
                    return;
                }

                const idx = container.querySelectorAll('[data-carousel-row]').length;
                const node = proto.cloneNode(true);
                node.querySelectorAll('[name]').forEach(function (inp) {
                    const n = inp.getAttribute('name');
                    if (n) {
                        inp.setAttribute('name', n.replace(/__INDEX__/g, String(idx)));
                    }
                });
                container.appendChild(node);
            };

            function handleDeleteCheckbox(target) {
                if (! target.checked) {
                    return;
                }

                const container = carouselContainer();
                if (! container) {
                    return;
                }

                const path = target.getAttribute('data-image-path');
                const row = target.closest('[data-carousel-row]');

                if (path) {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'deleted_sliders[][image]';
                    hidden.value = path;
                    container.appendChild(hidden);
                }

                if (row) {
                    row.remove();
                }
            }

            function onDocClick(e) {
                const t = elTarget(e);
                if (! t || ! t.closest) {
                    return;
                }

                const btn = t.closest('[data-shop-theme-carousel-add="' + themeId + '"]');
                if (btn) {
                    e.preventDefault();
                    e.stopPropagation();
                    window['shopThemeAddCarouselRow' + themeId]();
                }
            }

            function onDocChange(e) {
                const t = e.target;
                if (t && t.matches && t.matches('input[type="checkbox"][data-mark-delete="' + themeId + '"]')) {
                    handleDeleteCheckbox(t);
                }
            }

            document.addEventListener('click', onDocClick, true);
            document.addEventListener('change', onDocChange, true);
        })();
    </script>
@endpushOnce
