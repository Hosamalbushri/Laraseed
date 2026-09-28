<x-admin::layouts>
    <x-slot:title>
        @lang('student::app.students.index.title')
    </x-slot>

    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <div class="flex cursor-pointer items-center">
                    <x-admin::breadcrumbs name="students" />
                </div>

                <div class="text-xl font-bold dark:text-white">
                    @lang('student::app.students.index.title')
                </div>
            </div>

            <div class="flex items-center gap-x-2.5">
                @if (bouncer()->hasPermission('students.create'))
                    <a
                        href="{{ route('admin.students.create') }}"
                        class="primary-button"
                    >
                        @lang('student::app.students.index.create-btn')
                    </a>
                @endif
            </div>
        </div>

        <x-admin::datagrid :src="route('admin.students.index')" />
    </div>
</x-admin::layouts>
