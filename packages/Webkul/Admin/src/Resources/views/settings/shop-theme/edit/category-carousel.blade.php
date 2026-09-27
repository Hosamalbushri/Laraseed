<div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
    <p class="mb-1 text-base font-semibold text-gray-800 dark:text-white">
        @lang('admin::app.settings.shop-theme.edit.category-heading')
    </p>
    <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">
        @lang('admin::app.settings.shop-theme.edit.category-help')
    </p>

    <div class="grid gap-4 md:grid-cols-2">
        <x-admin::form.control-group class="!mb-0">
            <x-admin::form.control-group.label>
                @lang('admin::app.settings.shop-theme.edit.category-title')
            </x-admin::form.control-group.label>

            <x-admin::form.control-group.control
                type="text"
                name="options[title]"
                :value="$opts['title'] ?? ''"
                :label="trans('admin::app.settings.shop-theme.edit.category-title')"
            />
        </x-admin::form.control-group>

        <x-admin::form.control-group class="!mb-0">
            <x-admin::form.control-group.label>
                @lang('admin::app.settings.shop-theme.edit.category-limit')
            </x-admin::form.control-group.label>

            <x-admin::form.control-group.control
                type="text"
                name="options[limit]"
                rules="numeric"
                :value="$opts['limit'] ?? 24"
                :label="trans('admin::app.settings.shop-theme.edit.category-limit')"
            />
        </x-admin::form.control-group>
    </div>
</div>
