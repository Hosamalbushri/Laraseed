@php
    $title = $opts['title'] ?? '';
    $limit = (int) ($opts['limit'] ?? 8);
@endphp

<div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
    <p class="mb-1 text-base font-semibold text-gray-800 dark:text-white">
        @lang('admin::app.settings.shop-theme.edit.event-heading')
    </p>
    <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">
        @lang('admin::app.settings.shop-theme.edit.event-help')
    </p>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-admin::form.control-group class="!mb-0">
            <x-admin::form.control-group.label>
                @lang('admin::app.settings.shop-theme.edit.event-title')
            </x-admin::form.control-group.label>

            <x-admin::form.control-group.control
                type="text"
                name="options[title]"
                :value="old('options.title', $title)"
                :label="trans('admin::app.settings.shop-theme.edit.event-title')"
                placeholder="shop::app.home.event-carousel.title"
            />
        </x-admin::form.control-group>

        <x-admin::form.control-group class="!mb-0">
            <x-admin::form.control-group.label>
                @lang('admin::app.settings.shop-theme.edit.event-limit')
            </x-admin::form.control-group.label>

            <x-admin::form.control-group.control
                type="number"
                name="options[limit]"
                rules="numeric"
                :value="old('options.limit', $limit)"
                :label="trans('admin::app.settings.shop-theme.edit.event-limit')"
            />
        </x-admin::form.control-group>
    </div>
</div>
