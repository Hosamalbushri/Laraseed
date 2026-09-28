<x-admin::layouts>
    <x-slot:title>
        @lang('student::app.students.view.title', ['name' => $student->name])
    </x-slot>

    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <x-admin::breadcrumbs
                    name="students.view"
                    :entity="$student"
                />

                <div class="text-xl font-bold dark:text-white">
                    {{ $student->name }}
                </div>
            </div>

            @if (bouncer()->hasPermission('students.edit'))
                <a
                    href="{{ route('admin.students.edit', $student->id) }}"
                    class="primary-button"
                >
                    @lang('student::app.students.view.edit-btn')
                </a>
            @endif
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                    @lang('student::app.students.view.general-info')
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

                    <p class="text-gray-700 dark:text-gray-200"><span class="font-semibold text-gray-900 dark:text-gray-100">@lang('student::app.students.form.name'):</span> {{ $student->name }}</p>
                    <p class="text-gray-700 dark:text-gray-200"><span class="font-semibold text-gray-900 dark:text-gray-100">@lang('student::app.students.form.university-card-number'):</span> {{ $student->university_card_number }}</p>
                    <p class="text-gray-700 dark:text-gray-200"><span class="font-semibold text-gray-900 dark:text-gray-100">@lang('student::app.students.form.registration-number'):</span> {{ $student->registration_number ?: '—' }}</p>
                    <p class="text-gray-700 dark:text-gray-200"><span class="font-semibold text-gray-900 dark:text-gray-100">@lang('student::app.students.form.major'):</span> {{ $student->major ?: '—' }}</p>
                    <p class="text-gray-700 dark:text-gray-200"><span class="font-semibold text-gray-900 dark:text-gray-100">@lang('student::app.students.form.academic-level'):</span> {{ $student->academic_level ?: '—' }}</p>
                </div>
            </div>

            {!! view_render_event('admin.students.view.details.after', ['student' => $student]) !!}
        </div>
    </div>
</x-admin::layouts>
