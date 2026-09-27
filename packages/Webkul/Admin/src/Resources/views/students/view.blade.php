<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.students.view.title', ['name' => $student->name])
    </x-slot>

    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <x-admin::breadcrumbs
                    name="students.view"
                    :entity="$student"
                />

                <div class="text-xl font-bold dark:text-white">
                    @lang('admin::app.students.view.heading')
                </div>
            </div>

            @if (bouncer()->hasPermission('students.edit'))
                <a
                    href="{{ route('admin.students.edit', $student->id) }}"
                    class="secondary-button"
                >
                    @lang('admin::app.students.view.edit-btn')
                </a>
            @endif
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                    @lang('admin::app.students.view.general-info')
                </p>

                <div class="flex flex-col gap-3 text-sm">
                    @if ($student->profile_image)
                        <div>
                            <img
                                src="{{ Storage::url($student->profile_image) }}"
                                alt="{{ $student->name }}"
                                class="h-24 w-24 rounded-full object-cover"
                            />
                        </div>
                    @endif

                    <p class="text-gray-700 dark:text-gray-200"><span class="font-semibold text-gray-900 dark:text-gray-100">@lang('admin::app.students.form.name'):</span> {{ $student->name }}</p>
                    <p class="text-gray-700 dark:text-gray-200"><span class="font-semibold text-gray-900 dark:text-gray-100">@lang('admin::app.students.form.university-card-number'):</span> {{ $student->university_card_number }}</p>
                    <p class="text-gray-700 dark:text-gray-200"><span class="font-semibold text-gray-900 dark:text-gray-100">@lang('admin::app.students.form.registration-number'):</span> {{ $student->registration_number ?: '—' }}</p>
                    <p class="text-gray-700 dark:text-gray-200"><span class="font-semibold text-gray-900 dark:text-gray-100">@lang('admin::app.students.form.major'):</span> {{ $student->major ?: '—' }}</p>
                    <p class="text-gray-700 dark:text-gray-200"><span class="font-semibold text-gray-900 dark:text-gray-100">@lang('admin::app.students.form.academic-level'):</span> {{ $student->academic_level ?: '—' }}</p>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900 lg:col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <p class="text-base font-semibold text-gray-800 dark:text-white">
                        @lang('admin::app.students.subscriptions.title')
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
                                    :label="trans('admin::app.students.subscriptions.select-event')"
                                >
                                    <option value="">@lang('admin::app.students.subscriptions.select-event')</option>

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
                                @lang('admin::app.students.subscriptions.add-btn')
                            </button>
                        </div>
                    </x-admin::form>
                @endif

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="px-3 py-2 text-start font-semibold text-gray-700 dark:text-gray-200">@lang('admin::app.students.subscriptions.columns.event')</th>
                                <th class="px-3 py-2 text-start font-semibold text-gray-700 dark:text-gray-200">@lang('admin::app.students.subscriptions.columns.date')</th>
                                <th class="px-3 py-2 text-start font-semibold text-gray-700 dark:text-gray-200">@lang('admin::app.students.subscriptions.columns.status')</th>
                                <th class="px-3 py-2 text-end font-semibold text-gray-700 dark:text-gray-200">@lang('admin::app.students.subscriptions.columns.actions')</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($student->subscribedEvents as $event)
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="px-3 py-2 text-gray-800 dark:text-gray-100">{{ $event->title }}</td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ $event->event_date ? $event->event_date->format('Y-m-d') : '—' }}</td>
                                    <td class="px-3 py-2">
                                        @if ($event->status)
                                            <span class="badge badge-md badge-success">@lang('admin::app.students.subscriptions.published')</span>
                                        @else
                                            <span class="badge badge-md badge-danger">@lang('admin::app.students.subscriptions.unpublished')</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-end">
                                        @if (bouncer()->hasPermission('students.manage-subscriptions'))
                                            <x-admin::form
                                                :action="route('admin.students.subscriptions.delete', [$student->id, $event->id])"
                                                method="DELETE"
                                            >
                                                <button
                                                    type="submit"
                                                    class="secondary-button"
                                                >
                                                    @lang('admin::app.students.subscriptions.remove-btn')
                                                </button>
                                            </x-admin::form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="4"
                                        class="px-3 py-8 text-center text-gray-500 dark:text-gray-400"
                                    >
                                        @lang('admin::app.students.subscriptions.empty')
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-admin::layouts>
