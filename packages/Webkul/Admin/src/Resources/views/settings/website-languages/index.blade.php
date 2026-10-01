<x-admin::layouts>
    <x-slot:title>@lang('admin::website-languages.title')</x-slot>

    <div class="flex flex-col gap-4">
        <!-- Page Header -->
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <x-admin::breadcrumbs name="settings.website-languages" />

                <div class="text-xl font-bold dark:text-white">
                    @lang('admin::website-languages.title')
                </div>
            </div>

            <!-- Create Button -->
            <div class="flex items-center gap-x-2.5">
                @if (bouncer()->hasPermission('settings.website_languages.create'))
                    <button
                        type="button"
                        class="primary-button"
                        @click="$refs.languageSettings.openModal()"
                    >
                        @lang('admin::website-languages.add')
                    </button>
                @endif
            </div>
        </div>

        <!-- Informational Card -->
        <div class="flex flex-col gap-2 rounded-lg border border-gray-200 bg-white p-4 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <p class="text-gray-600 dark:text-gray-300">@lang('admin::website-languages.info')</p>
            <p class="text-gray-600 dark:text-gray-300">@lang('admin::website-languages.separate')</p>
            <div class="flex items-center gap-2 pt-1 font-medium text-gray-800 dark:text-white">
                <span>@lang('admin::website-languages.primary'):</span>
                <span class="rounded bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-900 dark:text-green-200">
                    {{ $primary->name }} ({{ $primary->code }})
                </span>
            </div>
        </div>

        <!-- DataGrid and Modal Component Host -->
        <v-website-language-settings ref="languageSettings">
            <x-admin::shimmer.datagrid />
        </v-website-language-settings>
    </div>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="website-language-settings-template"
        >
            <div>
                <!-- DataGrid -->
                <x-admin::datagrid
                    :src="route('admin.settings.website-languages.index')"
                    ref="datagrid"
                >
                    <template #body="{
                        isLoading,
                        available,
                        applied,
                        selectAll,
                        sort,
                        performAction
                    }">
                        <template v-if="isLoading">
                            <x-admin::shimmer.datagrid.table.body />
                        </template>

                        <template v-else>
                            <!-- Desktop Row View -->
                            <div
                                v-for="record in available.records"
                                class="row grid items-center gap-2.5 border-b px-4 py-4 text-gray-600 transition-all hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-950 max-lg:hidden"
                                :style="`grid-template-columns: repeat(${gridsCount}, minmax(0, 1fr))`"
                            >
                                <!-- ID -->
                                <p>@{{ record.id }}</p>

                                <!-- Code -->
                                <p class="font-medium text-gray-900 dark:text-white">@{{ record.code }}</p>

                                <!-- Name -->
                                <p>@{{ record.name }}</p>

                                <!-- Direction -->
                                <p v-html="record.direction"></p>

                                <!-- Status -->
                                <p v-html="record.is_active"></p>

                                <!-- Primary -->
                                <p v-html="record.is_primary"></p>

                                <!-- Sort Order -->
                                <p>@{{ record.sort_order }}</p>

                                <!-- Actions -->
                                <div class="flex items-center justify-end gap-1">
                                    <!-- Edit Action -->
                                    <a
                                        v-if="record.actions.find(action => action.index === 'edit')"
                                        @click="editModal(record)"
                                        title="@lang('admin::website-languages.edit')"
                                    >
                                        <span
                                            :class="record.actions.find(action => action.index === 'edit')?.icon"
                                            class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-gray-200 dark:hover:bg-gray-800 max-sm:place-self-center"
                                        >
                                        </span>
                                    </a>

                                    <!-- Activate Action -->
                                    <a
                                        v-if="!record.raw_is_active && record.actions.find(action => action.index === 'activate')"
                                        @click="performAction(record.actions.find(action => action.index === 'activate'))"
                                        title="@lang('admin::website-languages.activate')"
                                    >
                                        <span
                                            :class="record.actions.find(action => action.index === 'activate')?.icon"
                                            class="cursor-pointer rounded-md p-1.5 text-2xl text-green-600 transition-all hover:bg-gray-200 dark:hover:bg-gray-800 max-sm:place-self-center"
                                        >
                                        </span>
                                    </a>

                                    <!-- Deactivate Action -->
                                    <a
                                        v-if="record.raw_is_active && !record.raw_is_primary && record.actions.find(action => action.index === 'deactivate')"
                                        @click="performAction(record.actions.find(action => action.index === 'deactivate'))"
                                        title="@lang('admin::website-languages.deactivate')"
                                    >
                                        <span
                                            :class="record.actions.find(action => action.index === 'deactivate')?.icon"
                                            class="cursor-pointer rounded-md p-1.5 text-2xl text-red-600 transition-all hover:bg-gray-200 dark:hover:bg-gray-800 max-sm:place-self-center"
                                        >
                                        </span>
                                    </a>

                                    <!-- Make Primary Action -->
                                    <a
                                        v-if="!record.raw_is_primary && record.actions.find(action => action.index === 'primary')"
                                        @click="performAction(record.actions.find(action => action.index === 'primary'))"
                                        title="@lang('admin::website-languages.make-primary')"
                                    >
                                        <span
                                            :class="record.actions.find(action => action.index === 'primary')?.icon"
                                            class="cursor-pointer rounded-md p-1.5 text-2xl text-amber-500 transition-all hover:bg-gray-200 dark:hover:bg-gray-800 max-sm:place-self-center"
                                        >
                                        </span>
                                    </a>
                                </div>
                            </div>

                            <!-- Mobile Card View -->
                            <div
                                class="hidden border-b px-4 py-4 text-black dark:border-gray-800 dark:text-gray-300 max-lg:block"
                                v-for="record in available.records"
                            >
                                <div class="mb-2 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-gray-900 dark:text-white">@{{ record.name }}</span>
                                        <span class="text-xs text-gray-500">(@{{ record.code }})</span>
                                    </div>

                                    <div class="flex items-center gap-1">
                                        <!-- Edit Action -->
                                        <a
                                            v-if="record.actions.find(action => action.index === 'edit')"
                                            @click="editModal(record)"
                                            title="@lang('admin::website-languages.edit')"
                                        >
                                            <span
                                                :class="record.actions.find(action => action.index === 'edit')?.icon"
                                                class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-gray-200 dark:hover:bg-gray-800"
                                            >
                                            </span>
                                        </a>

                                        <!-- Activate Action -->
                                        <a
                                            v-if="!record.raw_is_active && record.actions.find(action => action.index === 'activate')"
                                            @click="performAction(record.actions.find(action => action.index === 'activate'))"
                                            title="@lang('admin::website-languages.activate')"
                                        >
                                            <span
                                                :class="record.actions.find(action => action.index === 'activate')?.icon"
                                                class="cursor-pointer rounded-md p-1.5 text-2xl text-green-600 transition-all hover:bg-gray-200 dark:hover:bg-gray-800"
                                            >
                                            </span>
                                        </a>

                                        <!-- Deactivate Action -->
                                        <a
                                            v-if="record.raw_is_active && !record.raw_is_primary && record.actions.find(action => action.index === 'deactivate')"
                                            @click="performAction(record.actions.find(action => action.index === 'deactivate'))"
                                            title="@lang('admin::website-languages.deactivate')"
                                        >
                                            <span
                                                :class="record.actions.find(action => action.index === 'deactivate')?.icon"
                                                class="cursor-pointer rounded-md p-1.5 text-2xl text-red-600 transition-all hover:bg-gray-200 dark:hover:bg-gray-800"
                                            >
                                            </span>
                                        </a>

                                        <!-- Make Primary Action -->
                                        <a
                                            v-if="!record.raw_is_primary && record.actions.find(action => action.index === 'primary')"
                                            @click="performAction(record.actions.find(action => action.index === 'primary'))"
                                            title="@lang('admin::website-languages.make-primary')"
                                        >
                                            <span
                                                :class="record.actions.find(action => action.index === 'primary')?.icon"
                                                class="cursor-pointer rounded-md p-1.5 text-2xl text-amber-500 transition-all hover:bg-gray-200 dark:hover:bg-gray-800"
                                            >
                                            </span>
                                        </a>
                                    </div>
                                </div>

                                <div class="grid gap-2 text-sm">
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-500">@lang('admin::website-languages.direction'):</span>
                                        <span v-html="record.direction"></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-500">@lang('admin::website-languages.status'):</span>
                                        <span v-html="record.is_active"></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-500">@lang('admin::website-languages.primary'):</span>
                                        <span v-html="record.is_primary"></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-500">@lang('admin::website-languages.order'):</span>
                                        <span>@{{ record.sort_order }}</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </template>
                </x-admin::datagrid>

                <!-- Modal for Create and Edit -->
                <x-admin::form
                    v-slot="{ meta, errors, handleSubmit }"
                    as="div"
                    ref="modalForm"
                >
                    <form @submit="handleSubmit($event, updateOrCreate)">
                        <x-admin::modal ref="languageModal">
                            <x-slot:header>
                                <p class="text-lg font-bold text-gray-800 dark:text-white">
                                    @{{
                                        selectedLanguage
                                        ? "@lang('admin::website-languages.edit')"
                                        : "@lang('admin::website-languages.add')"
                                    }}
                                </p>
                            </x-slot>

                            <x-slot:content>
                                <x-admin::form.control-group.control
                                    type="hidden"
                                    name="id"
                                />

                                <!-- Code -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::website-languages.code')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        id="code"
                                        name="code"
                                        rules="required"
                                        :label="trans('admin::website-languages.code')"
                                        :placeholder="trans('admin::website-languages.code')"
                                        ::disabled="!!selectedLanguage"
                                    />

                                    <x-admin::form.control-group.error control-name="code" />
                                </x-admin::form.control-group>

                                <!-- Name -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::website-languages.name')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        id="name"
                                        name="name"
                                        rules="required"
                                        :label="trans('admin::website-languages.name')"
                                        :placeholder="trans('admin::website-languages.name')"
                                    />

                                    <x-admin::form.control-group.error control-name="name" />
                                </x-admin::form.control-group>

                                <!-- Direction -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::website-languages.direction')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="select"
                                        id="direction"
                                        name="direction"
                                        rules="required"
                                        :label="trans('admin::website-languages.direction')"
                                    >
                                        <option value="ltr">@lang('admin::website-languages.ltr')</option>
                                        <option value="rtl">@lang('admin::website-languages.rtl')</option>
                                    </x-admin::form.control-group.control>

                                    <x-admin::form.control-group.error control-name="direction" />
                                </x-admin::form.control-group>

                                <!-- Sort Order -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::website-languages.order')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="number"
                                        id="sort_order"
                                        name="sort_order"
                                        rules="required|min:0"
                                        :label="trans('admin::website-languages.order')"
                                        :placeholder="trans('admin::website-languages.order')"
                                    />

                                    <x-admin::form.control-group.error control-name="sort_order" />
                                </x-admin::form.control-group>
                            </x-slot>

                            <x-slot:footer>
                                <x-admin::button
                                    button-type="submit"
                                    class="primary-button justify-center"
                                    :title="trans('admin::website-languages.save')"
                                    ::loading="isProcessing"
                                    ::disabled="isProcessing"
                                />
                            </x-slot>
                        </x-admin::modal>
                    </form>
                </x-admin::form>
            </div>
        </script>

        <script type="module">
            app.component('v-website-language-settings', {
                template: '#website-language-settings-template',

                data() {
                    return {
                        isProcessing: false,
                        selectedLanguage: null,
                    };
                },

                computed: {
                    gridsCount() {
                        let count = this.$refs.datagrid.available.columns.length;

                        if (this.$refs.datagrid.available.actions.length) {
                            ++count;
                        }

                        if (this.$refs.datagrid.available.massActions.length) {
                            ++count;
                        }

                        return count;
                    },
                },

                methods: {
                    openModal() {
                        this.selectedLanguage = null;

                        this.$refs.modalForm.resetForm({
                            values: {
                                id: null,
                                code: '',
                                name: '',
                                direction: 'ltr',
                                sort_order: 0,
                            }
                        });

                        this.$refs.languageModal.toggle();
                    },

                    editModal(record) {
                        this.selectedLanguage = record;

                        this.$refs.modalForm.setValues({
                            id: record.id,
                            code: record.code,
                            name: record.name,
                            direction: record.raw_direction || (String(record.direction).toLowerCase().includes('rtl') ? 'rtl' : 'ltr'),
                            sort_order: record.sort_order,
                        });

                        this.$refs.languageModal.toggle();
                    },

                    updateOrCreate(params, {resetForm, setErrors}) {
                        this.isProcessing = true;

                        const uri = params.id
                            ? "{{ route('admin.settings.website-languages.update', ':id') }}".replace(':id', params.id)
                            : "{{ route('admin.settings.website-languages.store') }}";

                        this.$axios.post(uri, {
                            ...params,
                            _method: params.id ? 'put' : 'post'
                        }, {
                            headers: {
                                'Content-Type': 'multipart/form-data',
                            }
                        }).then(response => {
                            this.isProcessing = false;

                            this.$refs.languageModal.toggle();

                            this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });

                            this.$refs.datagrid.get();

                            resetForm();
                        }).catch(error => {
                            this.isProcessing = false;

                            if (error.response?.status === 422) {
                                setErrors(error.response.data.errors);
                            } else if (error.response?.data?.message) {
                                this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });
                            }
                        });
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>
