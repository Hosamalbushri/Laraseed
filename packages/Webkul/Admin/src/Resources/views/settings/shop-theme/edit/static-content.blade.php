@php
    $html = $opts['html'] ?? '';
    $css = $opts['css'] ?? '';
    $adminInput =
        'w-full rounded border border-gray-200 px-2.5 py-2 text-sm font-normal text-gray-800 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400';
@endphp

<div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <div>
            <p class="text-base font-semibold text-gray-800 dark:text-white">
                @lang('admin::app.settings.shop-theme.edit.static-heading')
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                @lang('admin::app.settings.shop-theme.edit.static-help')
            </p>
        </div>

        <x-admin::button
            type="button"
            button-type="button"
            class="secondary-button"
            :title="trans('admin::app.settings.shop-theme.edit.add-image')"
            data-shop-theme-static-upload="{{ $theme->id }}"
        />
        <input
            id="shop-theme-static-img-{{ $theme->id }}"
            type="file"
            accept="image/jpeg,image/png,image/webp,image/gif"
            class="hidden"
        >
    </div>

    <div class="grid gap-4">
        <x-admin::form.control-group class="!mb-0">
            <x-admin::form.control-group.label>
                HTML
            </x-admin::form.control-group.label>

            <textarea
                id="shop-theme-html-{{ $theme->id }}"
                name="options[html]"
                rows="16"
                class="{{ $adminInput }} font-mono"
            >{{ old('options.html', $html) }}</textarea>
        </x-admin::form.control-group>

        <x-admin::form.control-group class="!mb-0">
            <x-admin::form.control-group.label>
                CSS
            </x-admin::form.control-group.label>

            <textarea
                name="options[css]"
                rows="10"
                class="{{ $adminInput }} font-mono"
            >{{ old('options.css', $css) }}</textarea>
        </x-admin::form.control-group>
    </div>
</div>

@pushOnce('scripts', 'shop-theme-static-{{ $theme->id }}')
    <script>
        (function () {
            const themeId = {{ $theme->id }};
            const uploadUrl = @json(route('admin.settings.shop-theme.store'));
            const csrf = @json(csrf_token());

            function elTarget(e) {
                const t = e.target;
                return t instanceof Element ? t : t && t.parentElement;
            }

            function onDocClick(e) {
                const t = elTarget(e);
                if (! t || ! t.closest) {
                    return;
                }

                if (t.closest('[data-shop-theme-static-upload="' + themeId + '"]')) {
                    e.preventDefault();
                    e.stopPropagation();
                    document.getElementById('shop-theme-static-img-' + themeId)?.click();
                }
            }

            function onDocChange(e) {
                const inp = e.target;
                if (! inp || inp.id !== 'shop-theme-static-img-' + themeId) {
                    return;
                }

                (async function () {
                    const file = inp.files && inp.files[0];
                    if (! file) {
                        return;
                    }

                    const formData = new FormData();
                    formData.append('id', String(themeId));
                    formData.append('image', file);
                    formData.append('_token', csrf);

                    const response = await fetch(uploadUrl, {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    });

                    if (! response.ok) {
                        inp.value = '';
                        return;
                    }

                    const url = await response.json();
                    const ta = document.getElementById('shop-theme-html-' + themeId);

                    if (ta && typeof url === 'string') {
                        ta.value += '<img src=' + JSON.stringify(url) + ' alt="" />\n';
                    }

                    inp.value = '';
                })();
            }

            document.addEventListener('click', onDocClick, true);
            document.addEventListener('change', onDocChange, true);
        })();
    </script>
@endpushOnce
