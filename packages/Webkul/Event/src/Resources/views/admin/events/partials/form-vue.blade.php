@php
    $eventsStorageBase = rtrim(asset('storage'), '/');
@endphp

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-event-fields-template"
    >
        <div class="flex flex-col gap-4">
            <input
                type="hidden"
                name="event_custom_fields_form"
                value="1"
            >

            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 pb-4 dark:border-gray-700">
                <p class="min-w-0 flex-1 text-base font-semibold text-gray-800 dark:text-white">
                    @lang('event::app.events.create.custom-fields.title')
                </p>

                <div
                    class="secondary-button shrink-0 text-sm"
                    v-on:click="addField"
                >
                    @lang('event::app.events.create.custom-fields.add-field')
                </div>
            </div>

            <div
                v-if="fields.length"
                class="block w-full overflow-x-auto rounded-lg border border-gray-200 bg-gray-50/80 shadow-sm dark:border-gray-700 dark:bg-gray-950/50"
            >
                <x-admin::table class="!min-w-[720px] !border-0">
                    <x-admin::table.thead class="text-sm font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-100">
                        <x-admin::table.thead.tr>
                            <x-admin::table.th class="!py-3 text-gray-700 dark:text-gray-100">
                                @lang('event::app.events.create.custom-fields.field-name')
                            </x-admin::table.th>

                            <x-admin::table.th class="!py-3 text-gray-700 dark:text-gray-100">
                                @lang('event::app.events.create.custom-fields.field-type')
                            </x-admin::table.th>

                            <x-admin::table.th class="!py-3 text-gray-700 dark:text-gray-100">
                                @lang('event::app.events.create.custom-fields.field-value')
                            </x-admin::table.th>

                            <x-admin::table.th class="!py-3 text-right text-gray-700 dark:text-gray-100">
                                @lang('event::app.events.create.custom-fields.action')
                            </x-admin::table.th>
                        </x-admin::table.thead.tr>
                    </x-admin::table.thead>

                    <x-admin::table.tbody>
                        <x-admin::table.thead.tr
                            class="border-b border-gray-100 bg-white text-gray-800 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-950/80"
                            v-for="(field, idx) in fields"
                            v-bind:key="idx"
                        >
                            <x-admin::table.td class="!align-top !py-4">
                                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-200">
                                    @lang('event::app.events.create.custom-fields.field-name')
                                </label>
                                <input
                                    type="text"
                                    v-model="field.name"
                                    :name="'fields[' + idx + '][name]'"
                                    class="w-full rounded border border-gray-200 px-2.5 py-2 text-sm text-gray-800 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200"
                                    placeholder="{{ __('event::app.events.create.custom-fields.placeholder-name') }}"
                                />
                            </x-admin::table.td>

                            <x-admin::table.td class="!align-top !py-4">
                                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-200">
                                    @lang('event::app.events.create.custom-fields.field-type')
                                </label>
                                <select
                                    v-model="field.type"
                                    :name="'fields[' + idx + '][type]'"
                                    @change="onFieldTypeChange(field)"
                                    class="custom-select min-w-[11rem] w-full cursor-pointer rounded border border-gray-200 px-2.5 py-2 text-sm text-gray-800 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200"
                                >
                                    <option value="text">@lang('event::app.events.create.custom-fields.type-text')</option>
                                    <option value="textarea">@lang('event::app.events.create.custom-fields.type-textarea')</option>
                                    <option value="number">@lang('event::app.events.create.custom-fields.type-number')</option>
                                    <option value="date">@lang('event::app.events.create.custom-fields.type-date')</option>
                                    <option value="time">@lang('event::app.events.create.custom-fields.type-time')</option>
                                    <option value="image">@lang('event::app.events.create.custom-fields.type-image')</option>
                                </select>
                            </x-admin::table.td>

                            <x-admin::table.td class="!align-top !py-4">
                                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-200">
                                    @lang('event::app.events.create.custom-fields.field-value')
                                </label>
                                <div v-bind:key="'event-field-val-' + idx + '-' + field.type">
                                    <template v-if="field.type === 'textarea'">
                                        <textarea
                                            v-model="field.value"
                                            :name="'fields[' + idx + '][value]'"
                                            class="w-full rounded border border-gray-200 px-2.5 py-2 text-sm text-gray-800 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200"
                                            placeholder="{{ __('event::app.events.create.custom-fields.placeholder-value') }}"
                                        ></textarea>
                                    </template>

                                    <template v-else-if="field.type === 'image'">
                                        <input
                                            type="hidden"
                                            :name="'fields[' + idx + '][old_value]'"
                                            :value="field.value"
                                        >

                                        <div
                                            v-if="field.value"
                                            class="mb-2"
                                        >
                                            <a
                                                v-bind:href="currentFileUrl(field.value)"
                                                target="_blank"
                                                class="text-xs font-medium text-brandColor hover:underline"
                                            >
                                                @lang('event::app.events.create.custom-fields.view-current-file')
                                            </a>
                                        </div>

                                        <input
                                            type="file"
                                            :name="'fields[' + idx + '][value]'"
                                            class="w-full rounded border border-gray-200 px-2.5 py-2 text-sm text-gray-800 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200"
                                        >
                                    </template>

                                    <template v-else-if="field.type === 'number'">
                                        <input
                                            type="number"
                                            v-model="field.value"
                                            :name="'fields[' + idx + '][value]'"
                                            class="w-full rounded border border-gray-200 px-2.5 py-2 text-sm text-gray-800 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200"
                                            placeholder="{{ __('event::app.events.create.custom-fields.placeholder-value') }}"
                                        >
                                    </template>

                                    <template v-else-if="field.type === 'date'">
                                        <input
                                            type="date"
                                            v-model="field.value"
                                            :name="'fields[' + idx + '][value]'"
                                            class="w-full rounded border border-gray-200 px-2.5 py-2 text-sm text-gray-800 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200"
                                        >
                                    </template>

                                    <template v-else-if="field.type === 'time'">
                                        <input
                                            type="time"
                                            v-model="field.value"
                                            :name="'fields[' + idx + '][value]'"
                                            class="w-full rounded border border-gray-200 px-2.5 py-2 text-sm text-gray-800 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200"
                                        >
                                    </template>

                                    <template v-else>
                                        <input
                                            type="text"
                                            v-model="field.value"
                                            :name="'fields[' + idx + '][value]'"
                                            class="w-full rounded border border-gray-200 px-2.5 py-2 text-sm text-gray-800 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200"
                                            placeholder="{{ __('event::app.events.create.custom-fields.placeholder-value') }}"
                                        >
                                    </template>
                                </div>
                            </x-admin::table.td>

                            <x-admin::table.td class="!align-top !px-4 !py-4 text-right">
                                <span
                                    class="icon-delete inline-flex cursor-pointer rounded-md p-1.5 text-2xl text-gray-600 transition-all hover:bg-gray-200 hover:text-red-600 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-red-400 max-sm:place-self-center"
                                    v-on:click="removeField(idx)"
                                >
                                </span>
                            </x-admin::table.td>
                        </x-admin::table.thead.tr>
                    </x-admin::table.tbody>
                </x-admin::table>
            </div>

            <div
                v-else
                class="rounded-lg border border-dashed border-gray-300 bg-gray-50 py-10 text-center text-sm text-gray-500 dark:border-gray-600 dark:bg-gray-900/40 dark:text-gray-400"
            >
                @lang('event::app.events.create.custom-fields.empty')
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-event-fields', {
            template: '#v-event-fields-template',

            props: ['dataData'],

            data() {
                return {
                    fields: this.dataData ? this.dataData : [],
                };
            },

            methods: {
                addField() {
                    this.fields.push({
                        name: '',
                        type: 'text',
                        value: '',
                    });
                },

                onFieldTypeChange(field) {
                    field.value = '';
                },

                removeField(rowIndex) {
                    this.fields.splice(rowIndex, 1);
                },

                currentFileUrl(path) {
                    const base = @json($eventsStorageBase);
                    const p = (path || '').replace(/^\//, '');

                    return p ? base + '/' + p : base;
                },
            },
        });
    </script>
@endPushOnce
