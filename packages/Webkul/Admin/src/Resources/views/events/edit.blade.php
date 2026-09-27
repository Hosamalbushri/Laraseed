<x-admin::layouts>
    <!-- Page Title -->
    <x-slot:title>
        @lang('admin::app.events.edit.title')
    </x-slot>

    <!-- Edit Form -->
    <x-admin::form
        :action="route('admin.events.update', $event->id)"
        method="PUT"
        enctype="multipart/form-data"
    >
        <div class="flex flex-col gap-4">
            <!-- Header Section -->
            <div
                class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
            >
                <div class="flex flex-col gap-2">
                    <x-admin::breadcrumbs
                        name="events.edit"
                        :entity="$event"
                    />

                    <div class="text-xl font-bold dark:text-white">
                        @lang('admin::app.events.edit.title')
                    </div>
                </div>

                <div class="flex items-center gap-x-2.5">
                    <div class="flex items-center gap-x-2.5">
                        <button
                            type="submit"
                            class="primary-button"
                        >
                            @lang('admin::app.events.edit.save-btn')
                        </button>
                    </div>
                </div>
            </div>

            <!-- body content -->
            <div class="flex gap-2.5 max-xl:flex-wrap">
                <!-- Left sub-component -->
                <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
                    <div
                        class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"
                    >
                        <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                            @lang('admin::app.events.create.general')
                        </p>

                        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            @lang('admin::app.events.create.section-required')
                        </p>

                        <!-- Title -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('admin::app.events.create.name')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                id="title"
                                name="title"
                                rules="required"
                                value="{{ old('title') ?: $event->title }}"
                                :label="trans('admin::app.events.create.name')"
                                :placeholder="trans('admin::app.events.create.placeholder-name')"
                            />

                            <x-admin::form.control-group.error control-name="title" />
                        </x-admin::form.control-group>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <!-- Event date -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.events.create.event-date')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="date"
                                    id="event_date"
                                    name="event_date"
                                    rules="required"
                                    value="{{ old('event_date') ?: ($event->event_date ? $event->event_date->format('Y-m-d') : '') }}"
                                    :label="trans('admin::app.events.create.event-date')"
                                />

                                <x-admin::form.control-group.error control-name="event_date" />
                            </x-admin::form.control-group>

                            <!-- Availability end date -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.events.create.event-end-date')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="date"
                                    id="event_end_date"
                                    name="event_end_date"
                                    value="{{ old('event_end_date') ?: ($event->event_end_date ? $event->event_end_date->format('Y-m-d') : '') }}"
                                    rules="required"
                                    :label="trans('admin::app.events.create.event-end-date')"
                                />

                                <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                    @lang('admin::app.events.create.event-end-date-hint')
                                </p>

                                <x-admin::form.control-group.error control-name="event_end_date" />
                            </x-admin::form.control-group>
                        </div>

                        <!-- Organizer -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('admin::app.events.create.organizer')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                id="organizer"
                                name="organizer"
                                rules="required"
                                value="{{ old('organizer') ?: $event->organizer }}"
                                :label="trans('admin::app.events.create.organizer')"
                                :placeholder="trans('admin::app.events.create.organizer-placeholder')"
                            />

                            <x-admin::form.control-group.error control-name="organizer" />
                        </x-admin::form.control-group>

                        <p class="mb-3 mt-6 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            @lang('admin::app.events.create.section-content')
                        </p>

                        <!-- Description -->
                        <x-admin::form.control-group class="!mb-0">
                            <x-admin::form.control-group.label>
                                @lang('admin::app.events.create.description')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="textarea"
                                id="description"
                                name="description"
                                tinymce="true"
                                :value="old('description') ?: $event->description"
                                :label="trans('admin::app.events.create.description')"
                                :placeholder="trans('admin::app.events.create.description')"
                            />

                            <x-admin::form.control-group.error control-name="description" />
                        </x-admin::form.control-group>
                    </div>

                    <!-- Repeater for Custom Fields -->
                    <div
                        class="box-shadow mt-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"
                    >
                        <v-event-fields :data-data='@json($event->fields ?? [])'></v-event-fields>
                    </div>

                    <!-- Related Events removed -->
                </div>

                <!-- Right sub-component -->
                <div class="flex w-[500px] max-w-full flex-col gap-2 max-sm:w-full">
                    <x-admin::accordion class="rounded-lg">
                        <x-slot:header>
                            <div class="flex items-center justify-between">
                                <p class="p-2.5 text-base font-semibold text-gray-800 dark:text-white">
                                    @lang('admin::app.events.create.details')
                                </p>
                            </div>
                        </x-slot>

                        <x-slot:content>
                            <!-- Categories (tree, Bagisto-style: load via API) -->
                            <x-admin::form.control-group class="!mb-4">
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.events.create.categories-tree')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.error control-name="category_ids" />

                                @include('admin::events.partials.event-categories-tree', [
                                    'selectedCategoryIds' => old('category_ids', $event->categories->pluck('id')->values()->all()),
                                ])

                                <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                                    @lang('admin::app.events.create.categories-tree-hint')
                                </p>
                            </x-admin::form.control-group>

                            <!-- Published -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.events.create.published')
                                </x-admin::form.control-group.label>

                                <input
                                    type="hidden"
                                    name="status"
                                    value="0"
                                />

                                <x-admin::form.control-group.control
                                    type="switch"
                                    name="status"
                                    value="1"
                                    :checked="filter_var(old('status', $event->status), FILTER_VALIDATE_BOOLEAN)"
                                    :label="trans('admin::app.events.create.published')"
                                />

                                <p class="mb-2 text-xs text-gray-600 dark:text-gray-400">
                                    @lang('admin::app.events.create.published-hint')
                                </p>

                                <x-admin::form.control-group.error control-name="status" />
                            </x-admin::form.control-group>

                            <!-- Images -->
                            <x-admin::form.control-group class="!mb-0">
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.events.create.image')
                                </x-admin::form.control-group.label>

                                <x-admin::media.images
                                    name="images"
                                    :allow-multiple="true"
                                    :uploaded-images="$event->images->map(fn ($img) => ['id' => $img->path, 'url' => Storage::url($img->path)])->values()->all()"
                                />

                                <x-admin::form.control-group.error control-name="images" />
                            </x-admin::form.control-group>
                        </x-slot>
                    </x-admin::accordion>

                    <x-admin::accordion class="rounded-lg">
                        <x-slot:header>
                            <div class="flex items-center justify-between">
                                <p class="p-2.5 text-base font-semibold text-gray-800 dark:text-white">
                                    @lang('admin::app.events.create.availability-section')
                                </p>
                            </div>
                        </x-slot>

                        <x-slot:content>
                            <p class="mb-4 text-xs text-gray-600 dark:text-gray-400">
                                @lang('admin::app.events.create.availability-section-intro')
                            </p>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.events.create.availability-use-seats')
                                </x-admin::form.control-group.label>

                                <input type="hidden" name="availability_use_seats" value="0" />

                                <x-admin::form.control-group.control
                                    type="switch"
                                    name="availability_use_seats"
                                    value="1"
                                    :checked="filter_var(old('availability_use_seats', $event->availability_use_seats ?? true), FILTER_VALIDATE_BOOLEAN)"
                                    :label="trans('admin::app.events.create.availability-use-seats')"
                                />

                                <p class="mb-2 text-xs text-gray-600 dark:text-gray-400">
                                    @lang('admin::app.events.create.availability-use-seats-hint')
                                </p>

                                <x-admin::form.control-group.error control-name="availability_use_seats" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group class="!mb-0">
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.events.create.available-seats')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="number"
                                    id="available_seats"
                                    name="available_seats"
                                    min="0"
                                    step="1"
                                    :value="old('available_seats', $event->available_seats ?? '')"
                                    :label="trans('admin::app.events.create.available-seats')"
                                    :placeholder="trans('admin::app.events.create.available-seats-placeholder')"
                                />

                                <p class="mb-2 text-xs text-gray-600 dark:text-gray-400">
                                    @lang('admin::app.events.create.available-seats-hint')
                                </p>

                                <x-admin::form.control-group.error control-name="available_seats" />
                            </x-admin::form.control-group>
                        </x-slot>
                    </x-admin::accordion>
                </div>
            </div>
        </div>
    </x-admin::form>

    @include('admin::events.partials.form-vue')
</x-admin::layouts>