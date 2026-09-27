@php
    $serviceIcons = [
        'icon-calendar',
        'icon-email',
        'icon-location',
        'icon-grid-view',
        'icon-heart',
        'icon-filter',
        'icon-download',
        'icon-eye',
        'icon-camera',
        'icon-cart',
    ];
    $services = $opts['services'] ?? [];
    if ($services === []) {
        $services = [
            ['service_icon' => 'icon-calendar', 'title' => '', 'description' => ''],
        ];
    }
    $adminInput =
        'w-full rounded border border-gray-200 px-2.5 py-2 text-sm font-normal text-gray-800 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400';
    $adminSelect =
        'custom-select w-full rounded border border-gray-200 px-2.5 py-2 text-sm font-normal text-gray-800 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400';
@endphp

<div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
    <p class="mb-1 text-base font-semibold text-gray-800 dark:text-white">
        @lang('admin::app.settings.shop-theme.edit.services-heading')
    </p>
    <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">
        @lang('admin::app.settings.shop-theme.edit.services-help')
    </p>

    <div
        id="shop-theme-services-rows"
        class="flex flex-col gap-4"
        data-admin-input-class="{{ $adminInput }}"
        data-admin-select-class="{{ $adminSelect }}"
    >
        @foreach ($services as $i => $svc)
            <div class="shop-theme-service-row rounded border border-slate-200 p-3 dark:border-gray-700">
                <div class="mb-2 flex justify-end">
                    <x-admin::button
                        type="button"
                        button-type="button"
                        class="transparent-button !h-auto !px-0 !py-0 text-xs text-red-600 hover:underline dark:text-red-400 shop-theme-remove-service"
                        :title="trans('admin::app.settings.shop-theme.edit.services-remove')"
                    />
                </div>

                <div class="grid gap-3 md:grid-cols-3">
                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.label>
                            @lang('admin::app.settings.shop-theme.edit.services-icon')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            name="services[{{ $i }}][service_icon]"
                            :value="$svc['service_icon'] ?? 'icon-calendar'"
                            :label="trans('admin::app.settings.shop-theme.edit.services-icon')"
                        >
                            @foreach ($serviceIcons as $ic)
                                <option value="{{ $ic }}" @selected(($svc['service_icon'] ?? '') === $ic)>
                                    {{ $ic }}
                                </option>
                            @endforeach
                        </x-admin::form.control-group.control>
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.label>
                            @lang('admin::app.settings.shop-theme.edit.services-title')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="services[{{ $i }}][title]"
                            :value="$svc['title'] ?? ''"
                            :label="trans('admin::app.settings.shop-theme.edit.services-title')"
                        />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.label>
                            @lang('admin::app.settings.shop-theme.edit.services-description')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="services[{{ $i }}][description]"
                            :value="$svc['description'] ?? ''"
                            :label="trans('admin::app.settings.shop-theme.edit.services-description')"
                        />
                    </x-admin::form.control-group>
                </div>
            </div>
        @endforeach
    </div>

    <x-admin::button
        type="button"
        button-type="button"
        id="shop-theme-add-service-{{ $theme->id }}"
        class="secondary-button mt-4 text-sm"
        :title="trans('admin::app.settings.shop-theme.edit.services-add')"
        data-shop-theme-add-service="{{ $theme->id }}"
    />
</div>

@pushOnce('scripts', 'shop-theme-services-{{ $theme->id }}')
    <script>
        (function () {
            const themeId = {{ $theme->id }};
            const icons = @json($serviceIcons);
            const svcLabels = @json([
                'remove' => trans('admin::app.settings.shop-theme.edit.services-remove'),
                'icon' => trans('admin::app.settings.shop-theme.edit.services-icon'),
                'title' => trans('admin::app.settings.shop-theme.edit.services-title'),
                'description' => trans('admin::app.settings.shop-theme.edit.services-description'),
            ]);

            function servicesWrap() {
                return document.getElementById('shop-theme-services-rows');
            }

            function adminInputClass() {
                const w = servicesWrap();
                return w?.dataset.adminInputClass || '';
            }

            function adminSelectClass() {
                const w = servicesWrap();
                return w?.dataset.adminSelectClass || '';
            }

            function nextServiceIndex() {
                const wrap = servicesWrap();
                return wrap ? wrap.querySelectorAll('.shop-theme-service-row').length : 0;
            }

            function rowTemplate(index) {
                let opts = icons.map(function (ic) {
                    return '<option value="' + ic + '">' + ic + '</option>';
                }).join('');

                const ic = adminInputClass();
                const sc = adminSelectClass();

                return (
                    '<div class="shop-theme-service-row rounded border border-slate-200 p-3 dark:border-gray-700">' +
                    '<div class="mb-2 flex justify-end">' +
                    '<button type="button" class="transparent-button !h-auto !px-0 !py-0 text-xs text-red-600 hover:underline dark:text-red-400 shop-theme-remove-service">' + svcLabels.remove + '</button>' +
                    '</div>' +
                    '<div class="grid gap-3 md:grid-cols-3">' +
                    '<div class="mb-4">' +
                    '<label class="mb-1.5 flex items-center gap-1 text-sm font-normal text-gray-800 dark:text-white">' + svcLabels.icon + '</label>' +
                    '<select name="services[' + index + '][service_icon]" class="' + sc + '">' +
                    opts +
                    '</select></div>' +
                    '<div class="mb-4">' +
                    '<label class="mb-1.5 flex items-center gap-1 text-sm font-normal text-gray-800 dark:text-white">' + svcLabels.title + '</label>' +
                    '<input type="text" name="services[' + index + '][title]" value="" class="' + ic + '"></div>' +
                    '<div class="mb-4">' +
                    '<label class="mb-1.5 flex items-center gap-1 text-sm font-normal text-gray-800 dark:text-white">' + svcLabels.description + '</label>' +
                    '<input type="text" name="services[' + index + '][description]" value="" class="' + ic + '"></div>' +
                    '</div></div>'
                );
            }

            function reindexServices() {
                const wrap = servicesWrap();
                if (! wrap) {
                    return;
                }

                wrap.querySelectorAll('.shop-theme-service-row').forEach(function (row, i) {
                    row.querySelectorAll('[name^="services["]').forEach(function (inp) {
                        const n = inp.getAttribute('name');
                        if (! n) {
                            return;
                        }

                        inp.setAttribute('name', n.replace(/services\[\d+\]/, 'services[' + i + ']'));
                    });
                });
            }

            function elTarget(e) {
                const t = e.target;
                return t instanceof Element ? t : t && t.parentElement;
            }

            function onDocClick(e) {
                const t = elTarget(e);
                if (! t || ! t.closest) {
                    return;
                }

                if (t.closest('[data-shop-theme-add-service="' + themeId + '"]')) {
                    e.preventDefault();
                    e.stopPropagation();
                    const wrap = servicesWrap();
                    if (! wrap) {
                        return;
                    }

                    const div = document.createElement('div');
                    div.innerHTML = rowTemplate(nextServiceIndex()).trim();
                    const row = div.firstElementChild;
                    if (row) {
                        wrap.appendChild(row);
                    }
                    return;
                }

                if (t.closest('.shop-theme-remove-service')) {
                    e.preventDefault();
                    e.stopPropagation();
                    const wrap = servicesWrap();
                    if (! wrap || wrap.querySelectorAll('.shop-theme-service-row').length <= 1) {
                        return;
                    }

                    const row = t.closest('.shop-theme-service-row');
                    if (row) {
                        row.remove();
                        reindexServices();
                    }
                }
            }

            document.addEventListener('click', onDocClick, true);
        })();
    </script>
@endpushOnce
