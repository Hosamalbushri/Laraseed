@if (bouncer()->hasPermission('students.create'))
    <div class="rounded-lg bg-white p-2 hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-950">
        <a href="{{ route('admin.students.create') }}">
            <div class="flex flex-col gap-1">
                <i class="icon-settings-user text-2xl text-gray-600"></i>

                <span class="font-medium dark:text-gray-300">@lang('student::app.students.index.create-btn')</span>
            </div>
        </a>
    </div>
@endif
