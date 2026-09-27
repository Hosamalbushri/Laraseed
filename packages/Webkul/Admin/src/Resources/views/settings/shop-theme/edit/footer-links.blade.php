@php
    $sections = $opts['sections'] ?? [];
    if ($sections === []) {
        $sections = [
            [
                'links' => [
                    ['title' => '', 'url' => '', 'sort_order' => 0],
                ],
            ],
        ];
    }
    $adminInput =
        'w-full rounded border border-gray-200 px-2.5 py-2 text-sm font-normal text-gray-800 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400';
@endphp

<div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
    <p class="mb-1 text-base font-semibold text-gray-800 dark:text-white">
        @lang('admin::app.settings.shop-theme.edit.footer-heading')
    </p>
    <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">
        @lang('admin::app.settings.shop-theme.edit.footer-help')
    </p>

    <div
        id="shop-theme-footer-sections"
        class="flex flex-col gap-4"
        data-label-title="{{ __('admin::app.settings.shop-theme.edit.footer-link-title') }}"
        data-label-url="{{ __('admin::app.settings.shop-theme.edit.footer-link-url') }}"
        data-label-column="{{ __('admin::app.settings.shop-theme.edit.footer-column') }}"
        data-label-remove-column="{{ __('admin::app.settings.shop-theme.edit.footer-remove-column') }}"
        data-label-add-link="{{ __('admin::app.settings.shop-theme.edit.footer-add-link') }}"
        data-input-class="{{ $adminInput }}"
    >
        @foreach ($sections as $si => $section)
            <div
                class="shop-theme-footer-section rounded border border-slate-200 p-3 dark:border-gray-700"
                data-section-index="{{ $si }}"
            >
                <div class="mb-2 flex items-center justify-between gap-2">
                    <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                        @lang('admin::app.settings.shop-theme.edit.footer-column')
                    </span>

                    <x-admin::button
                        type="button"
                        button-type="button"
                        class="transparent-button !h-auto !px-0 !py-0 text-xs text-red-600 hover:underline dark:text-red-400 shop-theme-remove-section"
                        :title="trans('admin::app.settings.shop-theme.edit.footer-remove-column')"
                    />
                </div>

                <div class="shop-theme-footer-links flex flex-col gap-3">
                    @foreach (($section['links'] ?? []) as $li => $link)
                        <div class="shop-theme-footer-link-row flex flex-wrap items-end gap-3">
                            <x-admin::form.control-group class="!mb-0 min-w-[140px] flex-1">
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.settings.shop-theme.edit.footer-link-title')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="footer[sections][{{ $si }}][links][{{ $li }}][title]"
                                    :value="$link['title'] ?? ''"
                                    :label="trans('admin::app.settings.shop-theme.edit.footer-link-title')"
                                />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group class="!mb-0 min-w-[180px] flex-[2]">
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.settings.shop-theme.edit.footer-link-url')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="footer[sections][{{ $si }}][links][{{ $li }}][url]"
                                    :value="$link['url'] ?? ''"
                                    :label="trans('admin::app.settings.shop-theme.edit.footer-link-url')"
                                />
                            </x-admin::form.control-group>

                            <input
                                type="hidden"
                                name="footer[sections][{{ $si }}][links][{{ $li }}][sort_order]"
                                value="{{ $li }}"
                            >

                            <x-admin::button
                                type="button"
                                button-type="button"
                                class="secondary-button mb-0.5 !min-h-0 border border-gray-300 px-2 py-1 text-xs dark:border-gray-600 shop-theme-remove-link"
                                :title="'×'"
                            />
                        </div>
                    @endforeach
                </div>

                <x-admin::button
                    type="button"
                    button-type="button"
                    class="secondary-button mt-3 text-xs shop-theme-add-link"
                    :title="trans('admin::app.settings.shop-theme.edit.footer-add-link')"
                />
            </div>
        @endforeach
    </div>

    <x-admin::button
        type="button"
        button-type="button"
        id="shop-theme-add-footer-section"
        class="secondary-button mt-4 text-sm"
        :title="trans('admin::app.settings.shop-theme.edit.footer-add-column')"
    />
</div>

@pushOnce('scripts', 'shop-theme-footer-links')
    <script>
        (function () {
            function rootEl() {
                return document.getElementById('shop-theme-footer-sections');
            }

            function inputClass() {
                const r = rootEl();
                return r?.dataset.inputClass || '';
            }

            function nextSectionIndex() {
                const root = rootEl();
                if (! root) {
                    return 0;
                }

                let max = -1;

                root.querySelectorAll('.shop-theme-footer-section').forEach(function (el) {
                    const i = parseInt(el.dataset.sectionIndex, 10);
                    if (! isNaN(i) && i > max) {
                        max = i;
                    }
                });

                return max + 1;
            }

            function reindexSection(section) {
                const si = section.dataset.sectionIndex;

                section.querySelectorAll('.shop-theme-footer-link-row').forEach(function (row, li) {
                    row.querySelectorAll('input[name^="footer"]').forEach(function (inp) {
                        const n = inp.getAttribute('name');
                        if (! n) {
                            return;
                        }

                        inp.setAttribute(
                            'name',
                            n.replace(
                                /footer\[sections\]\[[^\]]+\]\[links\]\[[^\]]+\]/,
                                'footer[sections][' + si + '][links][' + li + ']'
                            )
                        );

                        if (n.includes('[sort_order]')) {
                            inp.value = String(li);
                        }
                    });
                });
            }

            function addLinkRow(section) {
                const si = section.dataset.sectionIndex;
                const meta = rootEl();
                const lt = meta?.dataset.labelTitle || 'Title';
                const lu = meta?.dataset.labelUrl || 'URL';
                const ic = inputClass();
                const linksWrap = section.querySelector('.shop-theme-footer-links');
                if (! linksWrap) {
                    return;
                }

                const li = linksWrap.querySelectorAll('.shop-theme-footer-link-row').length;
                const row = document.createElement('div');
                row.className = 'shop-theme-footer-link-row flex flex-wrap items-end gap-3';
                row.innerHTML =
                    '<div class="mb-4 min-w-[140px] flex-1">' +
                    '<label class="mb-1.5 flex items-center gap-1 text-sm font-normal text-gray-800 dark:text-white">' + lt + '</label>' +
                    '<input type="text" name="footer[sections][' + si + '][links][' + li + '][title]" value="" class="' + ic + '">' +
                    '</div>' +
                    '<div class="mb-4 min-w-[180px] flex-[2]">' +
                    '<label class="mb-1.5 flex items-center gap-1 text-sm font-normal text-gray-800 dark:text-white">' + lu + '</label>' +
                    '<input type="text" name="footer[sections][' + si + '][links][' + li + '][url]" value="" class="' + ic + '">' +
                    '</div>' +
                    '<input type="hidden" name="footer[sections][' + si + '][links][' + li + '][sort_order]" value="' + li + '">' +
                    '<button type="button" class="secondary-button mb-0.5 border border-gray-300 px-2 py-1 text-xs dark:border-gray-600 shop-theme-remove-link">×</button>';
                linksWrap.appendChild(row);
            }

            function addFooterSection() {
                const root = rootEl();
                if (! root) {
                    return;
                }

                const si = nextSectionIndex();
                const lc = root.dataset.labelColumn || 'Column';
                const lrm = root.dataset.labelRemoveColumn || 'Remove';
                const lal = root.dataset.labelAddLink || 'Add link';
                const div = document.createElement('div');
                div.className = 'shop-theme-footer-section rounded border border-slate-200 p-3 dark:border-gray-700';
                div.dataset.sectionIndex = String(si);
                div.innerHTML =
                    '<div class="mb-2 flex items-center justify-between gap-2">' +
                    '<span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">' + lc + '</span>' +
                    '<button type="button" class="transparent-button !h-auto !px-0 !py-0 text-xs text-red-600 hover:underline dark:text-red-400 shop-theme-remove-section">' + lrm + '</button>' +
                    '</div>' +
                    '<div class="shop-theme-footer-links flex flex-col gap-3"></div>' +
                    '<button type="button" class="secondary-button mt-3 text-xs shop-theme-add-link">' + lal + '</button>';
                root.appendChild(div);
                addLinkRow(div);
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

                if (t.closest('#shop-theme-add-footer-section')) {
                    e.preventDefault();
                    e.stopPropagation();
                    addFooterSection();
                    return;
                }

                const root = rootEl();
                if (! root || ! root.contains(t)) {
                    return;
                }

                if (t.closest('.shop-theme-add-link')) {
                    e.preventDefault();
                    e.stopPropagation();
                    const section = t.closest('.shop-theme-footer-section');
                    if (section) {
                        addLinkRow(section);
                    }
                    return;
                }

                if (t.closest('.shop-theme-remove-link')) {
                    e.preventDefault();
                    e.stopPropagation();
                    const sec = t.closest('.shop-theme-footer-section');
                    const row = t.closest('.shop-theme-footer-link-row');
                    if (row) {
                        row.remove();
                    }
                    if (sec) {
                        reindexSection(sec);
                    }
                    return;
                }

                if (t.closest('.shop-theme-remove-section')) {
                    e.preventDefault();
                    e.stopPropagation();
                    const sec = t.closest('.shop-theme-footer-section');
                    const r = rootEl();
                    if (sec && r && r.querySelectorAll('.shop-theme-footer-section').length > 1) {
                        sec.remove();
                    }
                }
            }

            document.addEventListener('click', onDocClick, true);
        })();
    </script>
@endpushOnce
