<x-admin::layouts>
    <x-slot:title>
        {{ $contact->canonical_name }} - @lang('contacts_admin::app.admin.show.title')
    </x-slot>

    <div class="flex flex-col gap-4">
        <!-- Page Header -->
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2">
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $contact->type === 'person' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300' }}">
                        @lang('contacts_admin::app.admin.types.' . $contact->type)
                    </span>
                    <h1 class="text-xl font-bold dark:text-white">
                        {{ $contact->canonical_name }}
                    </h1>
                </div>
            </div>

            <div class="flex items-center gap-x-2.5">
                <a
                    href="{{ route('admin.contacts.index') }}"
                    class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                >
                    @lang('contacts_admin::app.admin.back_btn')
                </a>

                @if (bouncer()->hasPermission('contacts.edit'))
                    <a
                        href="{{ route('admin.contacts.edit', $contact->id) }}"
                        class="primary-button"
                    >
                        @lang('contacts_admin::app.admin.edit_contact')
                    </a>
                @endif
            </div>
        </div>

        <!-- Details Grid -->
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <!-- Left Column: Identity & Contact Methods -->
            <div class="flex flex-col gap-4">
                <!-- Identity Card -->
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                    <h2 class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('contacts_admin::app.admin.show.identity')
                    </h2>

                    <dl class="divide-y divide-gray-200 dark:divide-gray-800">
                        <div class="flex justify-between py-2 text-sm">
                            <dt class="font-medium text-gray-500 dark:text-gray-400">@lang('contacts_admin::app.admin.fields.type')</dt>
                            <dd class="font-semibold text-gray-900 dark:text-white">@lang('contacts_admin::app.admin.types.' . $contact->type)</dd>
                        </div>

                        <div class="flex justify-between py-2 text-sm">
                            <dt class="font-medium text-gray-500 dark:text-gray-400">@lang('contacts_admin::app.admin.datagrid.name')</dt>
                            <dd class="font-semibold text-gray-900 dark:text-white">{{ $contact->canonical_name }}</dd>
                        </div>

                        @if ($contact->type === 'person')
                            @if ($contact->first_name)
                                <div class="flex justify-between py-2 text-sm">
                                    <dt class="font-medium text-gray-500 dark:text-gray-400">@lang('contacts_admin::app.admin.fields.first_name')</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ $contact->first_name }}</dd>
                                </div>
                            @endif

                            @if ($contact->middle_name)
                                <div class="flex justify-between py-2 text-sm">
                                    <dt class="font-medium text-gray-500 dark:text-gray-400">@lang('contacts_admin::app.admin.fields.middle_name')</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ $contact->middle_name }}</dd>
                                </div>
                            @endif

                            @if ($contact->last_name)
                                <div class="flex justify-between py-2 text-sm">
                                    <dt class="font-medium text-gray-500 dark:text-gray-400">@lang('contacts_admin::app.admin.fields.last_name')</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ $contact->last_name }}</dd>
                                </div>
                            @endif

                            @if ($contact->job_title)
                                <div class="flex justify-between py-2 text-sm">
                                    <dt class="font-medium text-gray-500 dark:text-gray-400">@lang('contacts_admin::app.admin.fields.job_title')</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ $contact->job_title }}</dd>
                                </div>
                            @endif

                            @if ($contact->department)
                                <div class="flex justify-between py-2 text-sm">
                                    <dt class="font-medium text-gray-500 dark:text-gray-400">@lang('contacts_admin::app.admin.fields.department')</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ $contact->department }}</dd>
                                </div>
                            @endif
                        @else
                            @if ($contact->organization_name)
                                <div class="flex justify-between py-2 text-sm">
                                    <dt class="font-medium text-gray-500 dark:text-gray-400">@lang('contacts_admin::app.admin.fields.organization_name')</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ $contact->organization_name }}</dd>
                                </div>
                            @endif

                            @if ($contact->tax_number)
                                <div class="flex justify-between py-2 text-sm">
                                    <dt class="font-medium text-gray-500 dark:text-gray-400">@lang('contacts_admin::app.admin.fields.tax_number')</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ $contact->tax_number }}</dd>
                                </div>
                            @endif
                        @endif
                    </dl>
                </div>

                <!-- Communication Card -->
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                    <h2 class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('contacts_admin::app.admin.show.communication')
                    </h2>

                    <dl class="divide-y divide-gray-200 dark:divide-gray-800">
                        <div class="flex justify-between py-2 text-sm">
                            <dt class="font-medium text-gray-500 dark:text-gray-400">@lang('contacts_admin::app.admin.fields.email')</dt>
                            <dd class="text-gray-900 dark:text-white">
                                @if ($contact->email)
                                    <a href="mailto:{{ $contact->email }}" class="text-blue-600 hover:underline dark:text-blue-400">{{ $contact->email }}</a>
                                @else
                                    -
                                @endif
                            </dd>
                        </div>

                        <div class="flex justify-between py-2 text-sm">
                            <dt class="font-medium text-gray-500 dark:text-gray-400">@lang('contacts_admin::app.admin.fields.phone')</dt>
                            <dd class="text-gray-900 dark:text-white">
                                @if ($contact->phone)
                                    <a href="tel:{{ $contact->phone }}" class="text-blue-600 hover:underline dark:text-blue-400">{{ $contact->phone }}</a>
                                @else
                                    -
                                @endif
                            </dd>
                        </div>

                        <div class="flex justify-between py-2 text-sm">
                            <dt class="font-medium text-gray-500 dark:text-gray-400">@lang('contacts_admin::app.admin.fields.mobile')</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $contact->mobile ?: '-' }}</dd>
                        </div>

                        <div class="flex justify-between py-2 text-sm">
                            <dt class="font-medium text-gray-500 dark:text-gray-400">@lang('contacts_admin::app.admin.fields.website')</dt>
                            <dd class="text-gray-900 dark:text-white">
                                @if ($contact->website)
                                    <a href="{{ $contact->website }}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline dark:text-blue-400">{{ $contact->website }}</a>
                                @else
                                    -
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Right Column: Address, Notes & Status -->
            <div class="flex flex-col gap-4">
                <!-- Address Card -->
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                    <h2 class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('contacts_admin::app.admin.show.address')
                    </h2>

                    <dl class="divide-y divide-gray-200 dark:divide-gray-800">
                        <div class="flex justify-between py-2 text-sm">
                            <dt class="font-medium text-gray-500 dark:text-gray-400">@lang('contacts_admin::app.admin.fields.address_line1')</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $contact->address_line_1 ?: '-' }}</dd>
                        </div>

                        <div class="flex justify-between py-2 text-sm">
                            <dt class="font-medium text-gray-500 dark:text-gray-400">@lang('contacts_admin::app.admin.fields.address_line2')</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $contact->address_line_2 ?: '-' }}</dd>
                        </div>

                        <div class="flex justify-between py-2 text-sm">
                            <dt class="font-medium text-gray-500 dark:text-gray-400">@lang('contacts_admin::app.admin.fields.city')</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $contact->city ?: '-' }}</dd>
                        </div>

                        <div class="flex justify-between py-2 text-sm">
                            <dt class="font-medium text-gray-500 dark:text-gray-400">@lang('contacts_admin::app.admin.fields.state')</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $contact->state ?: '-' }}</dd>
                        </div>

                        <div class="flex justify-between py-2 text-sm">
                            <dt class="font-medium text-gray-500 dark:text-gray-400">@lang('contacts_admin::app.admin.fields.postal_code')</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $contact->postal_code ?: '-' }}</dd>
                        </div>

                        <div class="flex justify-between py-2 text-sm">
                            <dt class="font-medium text-gray-500 dark:text-gray-400">@lang('contacts_admin::app.admin.fields.country')</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $contact->country_code ?: '-' }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Status & Notes Card -->
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                    <h2 class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('contacts_admin::app.admin.show.notes')
                    </h2>

                    <dl class="divide-y divide-gray-200 dark:divide-gray-800">
                        <div class="flex justify-between py-2 text-sm">
                            <dt class="font-medium text-gray-500 dark:text-gray-400">@lang('contacts_admin::app.admin.fields.status')</dt>
                            <dd>
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $contact->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' }}">
                                    {{ $contact->is_active ? trans('contacts_admin::app.admin.status.active') : trans('contacts_admin::app.admin.status.inactive') }}
                                </span>
                            </dd>
                        </div>

                        <div class="flex flex-col py-2 text-sm">
                            <dt class="font-medium text-gray-500 dark:text-gray-400">@lang('contacts_admin::app.admin.fields.notes')</dt>
                            <dd class="mt-1 whitespace-pre-wrap text-gray-900 dark:text-white">{{ $contact->notes ?: '-' }}</dd>
                        </div>

                        <div class="flex justify-between py-2 text-sm">
                            <dt class="font-medium text-gray-500 dark:text-gray-400">@lang('contacts_admin::app.admin.datagrid.created_at')</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $contact->created_at }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</x-admin::layouts>
