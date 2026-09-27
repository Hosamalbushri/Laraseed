<x-admin::layouts>
    <!-- Page Title -->
    <x-slot:title>
        @lang('admin::app.event-categories.edit.title')
    </x-slot>

    <!-- Edit Form -->
    <x-admin::form
        :action="route('admin.events.categories.update', $category->id)"
        method="PUT"
    >
        <div class="flex flex-col gap-4">
            <!-- Header Section -->
            <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <div class="flex flex-col gap-2">
                    <x-admin::breadcrumbs name="categories.edit" :entity="$category" />

                    <div class="text-xl font-bold dark:text-white">
                        @lang('admin::app.event-categories.edit.title')
                    </div>
                </div>

                <div class="flex items-center gap-x-2.5">
                    <div class="flex items-center gap-x-2.5">
                        <button
                            type="submit"
                            class="primary-button"
                        >
                            @lang('admin::app.event-categories.edit.save-btn')
                        </button>
                    </div>
                </div>
            </div>

            <!-- body content -->
            <div class="flex gap-2.5 max-xl:flex-wrap">
                <!-- Left sub-component -->
                <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
                    <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                        <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                            @lang('admin::app.event-categories.create.general')
                        </p>

                        <!-- Name -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('admin::app.event-categories.create.name')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                id="name"
                                name="name"
                                rules="required"
                                value="{{ old('name') ?: $category->name }}"
                                :label="trans('admin::app.event-categories.create.name')"
                                :placeholder="trans('admin::app.event-categories.create.placeholder-name')"
                            />

                            <x-admin::form.control-group.error control-name="name" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('admin::app.event-categories.create.parent')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                id="parent_id"
                                name="parent_id"
                                :label="trans('admin::app.event-categories.create.parent')"
                                :value="old('parent_id', $category->parent_id)"
                            >
                                <option value="">@lang('admin::app.event-categories.create.parent-none')</option>
                                @foreach ($parentCategories as $p)
                                    <option value="{{ $p->id }}" @selected((int) old('parent_id', $category->parent_id) === (int) $p->id)>
                                        {{ $p->name }}
                                    </option>
                                @endforeach
                            </x-admin::form.control-group.control>

                            <x-admin::form.control-group.error control-name="parent_id" />
                        </x-admin::form.control-group>

                        <!-- Description -->
                        <x-admin::form.control-group class="!mb-0">
                            <x-admin::form.control-group.label>
                                @lang('admin::app.event-categories.create.description')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="textarea"
                                id="description"
                                name="description"
                                :value="old('description') ?: $category->description"
                                :label="trans('admin::app.event-categories.create.description')"
                                :placeholder="trans('admin::app.event-categories.create.description')"
                            />

                            <x-admin::form.control-group.error control-name="description" />
                        </x-admin::form.control-group>
                    </div>
                </div>

                <!-- Right sub-component -->
                <div class="flex w-[360px] max-w-full flex-col gap-2 max-sm:w-full">
                    <x-admin::accordion class="rounded-lg">
                        <x-slot:header>
                            <div class="flex items-center justify-between">
                                <p class="p-2.5 text-base font-semibold text-gray-800 dark:text-white">
                                    @lang('admin::app.event-categories.create.options')
                                </p>
                            </div>
                        </x-slot>

                        <x-slot:content>
                            <!-- Status -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.event-categories.create.status')
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
                                    :checked="old('status') ?? $category->status ? true : false"
                                    :label="trans('admin::app.event-categories.create.status')"
                                />

                                <x-admin::form.control-group.error control-name="status" />
                            </x-admin::form.control-group>

                            <!-- Sort Order -->
                            <x-admin::form.control-group class="!mb-0">
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.event-categories.create.sort_order')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    id="sort_order"
                                    name="sort_order"
                                    :value="old('sort_order') ?: $category->sort_order"
                                    :label="trans('admin::app.event-categories.create.sort_order')"
                                />

                                <x-admin::form.control-group.error control-name="sort_order" />
                            </x-admin::form.control-group>
                        </x-slot>
                    </x-admin::accordion>
                </div>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts>
