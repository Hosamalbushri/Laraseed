<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.students.index.title')
    </x-slot>

    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <x-admin::breadcrumbs name="students" />

                <div class="text-xl font-bold dark:text-white">
                    @lang('admin::app.students.index.title')
                </div>
            </div>

            @if (bouncer()->hasPermission('students.create'))
                <a
                    href="{{ route('admin.students.create') }}"
                    class="primary-button"
                >
                    @lang('admin::app.students.index.create-btn')
                </a>
            @endif
        </div>

        <x-admin::datagrid :src="route('admin.students.index')">
            <x-slot:toolbar-right-before>
                <x-admin::datagrid.export :src="route('admin.students.index')" />
            </x-slot>

            <x-admin::shimmer.datagrid />
        </x-admin::datagrid>
    </div>
</x-admin::layouts>
