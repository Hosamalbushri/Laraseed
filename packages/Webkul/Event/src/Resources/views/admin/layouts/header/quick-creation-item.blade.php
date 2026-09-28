@if (bouncer()->hasPermission('events.create'))
    <div class="rounded-lg bg-white p-2 hover:bg-gray-100 dark:bg-gray-800 dark:bg-gray-950">
        <a href="{{ route('admin.events.create') }}">
            <div class="flex flex-col gap-1">
                <i class="icon-product text-2xl text-gray-600"></i>

                <span class="font-medium dark:text-gray-300">@lang('event::app.events.index.create-btn')</span>
            </div>
        </a>
    </div>
@endif
