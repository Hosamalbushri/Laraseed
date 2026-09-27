<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.settings.shop-theme.index.title')
    </x-slot>

    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <x-admin::breadcrumbs name="settings.shop_theme" />

                <div class="text-xl font-bold dark:text-gray-300">
                    @lang('admin::app.settings.shop-theme.index.title')
                </div>

                <p class="max-w-3xl text-xs text-gray-500 dark:text-gray-400">
                    @lang('admin::app.settings.shop-theme.index.info')
                </p>
            </div>

            <div class="flex items-center gap-x-2.5">
                @if (bouncer()->hasPermission('settings.shop_theme.create'))
                    {{-- Native button: @click must bind on a real element; v-button does not forward listeners here. --}}
                    <button
                        type="button"
                        class="primary-button"
                        @click="$refs.shopThemeCreate.openModal()"
                    >
                        @lang('admin::app.settings.shop-theme.index.create-btn')
                    </button>

                    <v-create-shop-theme-form ref="shopThemeCreate" />
                @endif
            </div>
        </div>

        <x-admin::datagrid :src="route('admin.settings.shop-theme.index')" />
    </div>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-create-shop-theme-form-template"
        >
            <div>
                <x-admin::form
                    v-slot="{ meta, errors, handleSubmit }"
                    as="div"
                >
                    <form @submit="handleSubmit($event, create)">
                        <x-admin::modal ref="themeCreateModal">
                            <x-slot:header>
                                <p class="text-lg font-bold text-gray-800 dark:text-white">
                                    @lang('admin::app.settings.shop-theme.create.title')
                                </p>
                            </x-slot>

                            <x-slot:content>
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.settings.shop-theme.create.name')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="name"
                                        rules="required"
                                        :label="trans('admin::app.settings.shop-theme.create.name')"
                                        :placeholder="trans('admin::app.settings.shop-theme.create.name')"
                                    />

                                    <x-admin::form.control-group.error control-name="name" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.settings.shop-theme.create.sort-order')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="sort_order"
                                        rules="required|numeric"
                                        :label="trans('admin::app.settings.shop-theme.create.sort-order')"
                                        :placeholder="trans('admin::app.settings.shop-theme.create.sort-order')"
                                    />

                                    <x-admin::form.control-group.error control-name="sort_order" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.settings.shop-theme.create.type.title')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="type"
                                        rules="required"
                                        value="event_carousel"
                                    >
                                        <option
                                            v-for="(type, key) in themeTypes"
                                            :value="key"
                                            :text="type"
                                        >
                                        </option>
                                    </x-admin::form.control-group.control>

                                    <x-admin::form.control-group.error control-name="type" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.settings.shop-theme.create.theme-code')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="theme_code"
                                        rules="required"
                                        value="{{ array_key_first(config('shop.theme_definitions', ['default' => []])) ?: 'default' }}"
                                    >
                                        @foreach (config('shop.theme_definitions', []) as $code => $def)
                                            <option value="{{ $code }}" v-pre>
                                                {{ $def['name'] ?? $code }}
                                            </option>
                                        @endforeach
                                    </x-admin::form.control-group.control>

                                    <x-admin::form.control-group.error control-name="theme_code" />
                                </x-admin::form.control-group>
                            </x-slot>

                            <x-slot:footer>
                                <x-admin::button
                                    button-type="submit"
                                    class="primary-button"
                                    :title="trans('admin::app.settings.shop-theme.create.save-btn')"
                                    ::loading="isLoading"
                                    ::disabled="isLoading"
                                />
                            </x-slot>
                        </x-admin::modal>
                    </form>
                </x-admin::form>
            </div>
        </script>

        <script type="module">
            app.component('v-create-shop-theme-form', {
                template: '#v-create-shop-theme-form-template',

                data() {
                    return {
                        themeTypes: {
                            image_carousel: "@lang('admin::app.settings.shop-theme.create.type.image-carousel')",
                            static_content: "@lang('admin::app.settings.shop-theme.create.type.static-content')",
                            event_carousel: "@lang('admin::app.settings.shop-theme.create.type.event-carousel')",
                            category_carousel: "@lang('admin::app.settings.shop-theme.create.type.category-carousel')",
                            footer_links: "@lang('admin::app.settings.shop-theme.create.type.footer-links')",
                            services_content: "@lang('admin::app.settings.shop-theme.create.type.services-content')",
                            immersive_hero: "@lang('admin::app.settings.shop-theme.create.type.immersive-hero')",
                            portal_footer: "@lang('admin::app.settings.shop-theme.create.type.portal-footer')",
                        },

                        isLoading: false,
                    };
                },

                methods: {
                    openModal() {
                        this.$refs.themeCreateModal.toggle();
                    },

                    create(params, { setErrors }) {
                        this.isLoading = true;

                        this.$axios.post('{{ route('admin.settings.shop-theme.store') }}', params)
                            .then((response) => {
                                this.isLoading = false;

                                if (response.data.redirect_url) {
                                    window.location.href = response.data.redirect_url;
                                }
                            })
                            .catch((error) => {
                                this.isLoading = false;

                                if (error.response?.status === 422) {
                                    setErrors(error.response.data.errors);
                                }
                            });
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>
