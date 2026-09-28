<div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900 lg:col-span-2">
    <div class="mb-4 flex items-center justify-between">
        <p class="text-base font-semibold text-gray-800 dark:text-white">
            @lang('event::app.students.subscriptions.title')
        </p>
    </div>

    @if (bouncer()->hasPermission('students.manage-subscriptions'))
        <x-admin::form
            :action="route('admin.students.subscriptions.store', $student->id)"
            method="POST"
        >
            <div class="mb-4 grid gap-2 sm:grid-cols-[1fr_auto]">
                <x-admin::form.control-group class="!mb-0">
                    <x-admin::form.control-group.control
                        type="select"
                        name="event_id"
                        rules="required"
                        :label="trans('event::app.students.subscriptions.select-event')"
                    >
                        <option value="">@lang('event::app.students.subscriptions.select-event')</option>

                        @foreach ($events as $event)
                            <option value="{{ $event->id }}">
                                {{ $event->title }}{{ $event->event_date ? ' - '.$event->event_date->format('Y-m-d') : '' }}
                            </option>
                        @endforeach
                    </x-admin::form.control-group.control>

                    <x-admin::form.control-group.error control-name="event_id" />
                </x-admin::form.control-group>

                <button
                    type="submit"
                    class="primary-button max-sm:w-full"
                >
                    @lang('event::app.students.subscriptions.add-btn')
                </button>
            </div>
        </x-admin::form>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="px-3 py-2 text-start font-semibold text-gray-700 dark:text-gray-200">@lang('event::app.students.subscriptions.columns.event')</th>
                    <th class="px-3 py-2 text-start font-semibold text-gray-700 dark:text-gray-200">@lang('event::app.students.subscriptions.columns.date')</th>
                    <th class="px-3 py-2 text-start font-semibold text-gray-700 dark:text-gray-200">@lang('event::app.students.subscriptions.columns.status')</th>
                    @if (bouncer()->hasPermission('students.manage-subscriptions'))
                        <th class="px-3 py-2 text-end font-semibold text-gray-700 dark:text-gray-200">@lang('event::app.students.subscriptions.columns.actions')</th>
                    @endif
                </tr>
            </thead>

            <tbody>
                @forelse ($subscribedEvents as $event)
                    <tr class="border-b border-gray-100 last:border-b-0 dark:border-gray-800">
                        <td class="px-3 py-2">
                            @if (bouncer()->hasPermission('events.edit'))
                                <a
                                    href="{{ route('admin.events.edit', $event->id) }}"
                                    class="text-brandColor hover:underline"
                                >
                                    {{ $event->title }}
                                </a>
                            @else
                                {{ $event->title }}
                            @endif
                        </td>
                        <td class="px-3 py-2 text-gray-600 dark:text-gray-300">
                            {{ $event->event_date ? $event->event_date->format('Y-m-d') : '—' }}
                        </td>
                        <td class="px-3 py-2">
                            @if ($event->status)
                                <span class="badge badge-sm badge-success">@lang('event::app.students.subscriptions.published')</span>
                            @else
                                <span class="badge badge-sm badge-danger">@lang('event::app.students.subscriptions.unpublished')</span>
                            @endif
                        </td>
                        @if (bouncer()->hasPermission('students.manage-subscriptions'))
                            <td class="px-3 py-2 text-end">
                                <form
                                    action="{{ route('admin.students.subscriptions.delete', [$student->id, $event->id]) }}"
                                    method="POST"
                                    onsubmit="return confirm('{{ trans('admin::app.ui.delete-confirm') }}');"
                                    class="inline"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="cursor-pointer text-red-600 hover:text-red-800 dark:text-red-400"
                                    >
                                        @lang('event::app.students.subscriptions.remove-btn')
                                    </button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-3 py-6 text-center text-gray-500">
                            @lang('event::app.students.subscriptions.empty')
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
