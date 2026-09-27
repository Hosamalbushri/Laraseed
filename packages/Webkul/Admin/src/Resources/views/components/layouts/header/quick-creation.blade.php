<div>
    @if (bouncer()->hasPermission('events.create')
        || bouncer()->hasPermission('students.create')
        || bouncer()->hasPermission('settings.user.roles.create')
        || bouncer()->hasPermission('settings.user.users.create')
    )
        <x-admin::dropdown position="bottom-right">
            <x-slot:toggle>
                <!-- Toggle Button -->
                <button class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full bg-brandColor text-white">
                    <i class="icon-add text-2xl"></i>
                </button>
            </x-slot>

            <!-- Dropdown Content -->
            <x-slot:content class="mt-2 !p-0">
                <div class="relative px-2 py-4">
                    <div class="grid grid-cols-2 gap-2 text-center">
                        <!-- Link to create new Event -->
                        @if (bouncer()->hasPermission('events.create'))
                            <div class="rounded-lg bg-white p-2 hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-950">
                                <a href="{{ route('admin.events.create') }}">
                                    <div class="flex flex-col gap-1">
                                        <i class="icon-product text-2xl text-gray-600"></i>

                                        <span class="font-medium dark:text-gray-300">@lang('admin::app.events.index.create-btn')</span>
                                    </div>
                                </a>
                            </div>
                        @endif

                        <!-- Link to create new Student -->
                        @if (bouncer()->hasPermission('students.create'))
                            <div class="rounded-lg bg-white p-2 hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-950">
                                <a href="{{ route('admin.students.create') }}">
                                    <div class="flex flex-col gap-1">
                                        <i class="icon-settings-user text-2xl text-gray-600"></i>

                                        <span class="font-medium dark:text-gray-300">@lang('admin::app.students.index.create-btn')</span>
                                    </div>
                                </a>
                            </div>
                        @endif

                        <!-- Link to create new Role -->
                        @if (bouncer()->hasPermission('settings.user.roles.create'))
                            <div class="rounded-lg bg-white p-2 hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-950">
                                <a href="{{ route('admin.settings.roles.create') }}">
                                    <div class="flex flex-col gap-1">
                                        <i class="icon-role text-2xl text-gray-600"></i>

                                        <span class="font-medium dark:text-gray-300">@lang('admin::app.layouts.role')</span>
                                    </div>
                                </a>
                            </div>
                        @endif

                        <!-- Link to create new User-->
                        @if (bouncer()->hasPermission('settings.user.users.create'))
                            <div class="rounded-lg bg-white p-2 hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-950">
                                <a href="{{ route('admin.settings.users.index', ['action' => 'create']) }}">
                                    <div class="flex flex-col gap-1">
                                        <i class="icon-user text-2xl text-gray-600"></i>

                                        <span class="font-medium dark:text-gray-300">@lang('admin::app.layouts.user')</span>
                                    </div>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </x-slot>
        </x-admin::dropdown>
    @endif
</div>
