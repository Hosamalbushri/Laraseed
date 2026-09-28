<x-admin::layouts>
    <x-slot:title>@lang('lost_found::app.employee.claims.title')</x-slot>

    <div class="flex flex-col gap-4">
        <div class="rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
            <h1 class="text-xl font-bold">@lang('lost_found::app.employee.claims.title')</h1>
            <p>{{ $item->public_reference }} — {{ $item->title }} ({{ trans('lost_found::app.employee.items.statuses.'.$item->status) }})</p>
        </div>

        <x-admin::datagrid :src="route('admin.lost_found.items.claims.index', $item->id)">
            <x-admin::shimmer.datagrid />
        </x-admin::datagrid>
    </div>
</x-admin::layouts>
