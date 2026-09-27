<v-mobile-mega-search>
    <i class="icon-search flex items-center text-2xl"></i>
</v-mobile-mega-search>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-mobile-mega-search-template"
    >
        <div>
            <i
                class="icon-search flex items-center text-2xl"
                @click="toggleSearchInput"
                v-show="!isSearchVisible"
            ></i>

            <div
                v-show="isSearchVisible"
                class="absolute left-1/2 top-3 z-[10002] flex w-full max-w-full -translate-x-1/2 items-center px-2"
            >
                <i class="icon-search absolute top-2 flex items-center text-2xl ltr:left-4 rtl:right-4"></i>

                <input
                    type="text"
                    class="peer block w-full rounded-3xl border bg-white px-10 py-1.5 leading-6 text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
                    :class="{'border-gray-400': isDropdownOpen}"
                    placeholder="@lang('admin::app.components.layouts.header.mega-search.title')"
                    v-model.lazy="searchTerm"
                    @click="searchTerm.length >= 2 ? isDropdownOpen = true : {}"
                    v-debounce="500"
                    ref="searchInput"
                >

                <i class="icon-cross-large absolute top-2 flex items-center text-2xl ltr:right-4 rtl:left-4"></i>

                <div
                    class="absolute left-[6px] right-[6px] top-10 z-10 max-h-[80vh] overflow-y-auto rounded-lg border bg-white shadow-[0px_0px_0px_0px_rgba(0,0,0,0.10),0px_1px_3px_0px_rgba(0,0,0,0.10),0px_5px_5px_0px_rgba(0,0,0,0.09),0px_12px_7px_0px_rgba(0,0,0,0.05),0px_22px_9px_0px_rgba(0,0,0,0.01),0px_34px_9px_0px_rgba(0,0,0,0.00)] dark:border-gray-800 dark:bg-gray-900"
                    v-if="isDropdownOpen"
                >
                    <!-- Search Tabs -->
                    <div class="flex overflow-x-auto border-b text-sm text-gray-600 dark:border-gray-800 dark:text-gray-300 sm:text-base">
                        <div
                            class="flex-shrink-0 cursor-pointer whitespace-nowrap px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-950 sm:px-4 sm:py-3"
                            :class="{ 'border-b-2 border-brandColor': activeTab == tab.key }"
                            v-for="tab in tabs"
                            @click="activeTab = tab.key; search();"
                        >
                            @{{ tab.title }}
                        </div>
                    </div>

                    <!-- Searched Results -->
                    <template v-if="activeTab == 'events'">
                        <template v-if="isLoading">
                            <x-admin::shimmer.header.mega-search.products />
                        </template>

                        <template v-else>
                            <div class="grid max-h-[400px] overflow-y-auto">
                                <template v-for="event in searchedResults.events">
                                    <a
                                        :href="'{{ route('admin.events.edit', ':id') }}'.replace(':id', event.id)"
                                        class="flex cursor-pointer justify-between gap-2.5 border-b border-slate-300 p-4 last:border-b-0 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-950"
                                    >
                                        <div class="grid place-content-start gap-1.5">
                                            <p class="text-gray-600 dark:text-gray-300">
                                                @{{ event.name }}
                                            </p>
                                        </div>
                                    </a>
                                </template>
                            </div>

                            <div class="flex border-t p-3 dark:border-gray-800">
                                <a
                                    href="{{ route('admin.events.index') }}"
                                    class="cursor-pointer text-xs font-semibold text-brandColor transition-all hover:underline"
                                >
                                    @lang('admin::app.components.layouts.header.mega-search.explore-all-events')
                                </a>
                            </div>
                        </template>
                    </template>

                    <template v-if="activeTab == 'students'">
                        <template v-if="isLoading">
                            <x-admin::shimmer.header.mega-search.persons />
                        </template>

                        <template v-else>
                            <div class="grid max-h-[400px] overflow-y-auto">
                                <template v-for="student in searchedResults.students">
                                    <a
                                        :href="'{{ route('admin.students.view', ':id') }}'.replace(':id', student.id)"
                                        class="flex cursor-pointer justify-between gap-2.5 border-b border-slate-300 p-4 last:border-b-0 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-950"
                                    >
                                        <div class="grid place-content-start gap-1.5">
                                            <p class="text-gray-600 dark:text-gray-300">
                                                @{{ student.name }}
                                            </p>

                                            <p class="text-gray-500">
                                                @{{ student.university_card_number }}
                                            </p>
                                        </div>
                                    </a>
                                </template>
                            </div>

                            <div class="flex border-t p-3 dark:border-gray-800">
                                <a
                                    href="{{ route('admin.students.index') }}"
                                    class="cursor-pointer text-xs font-semibold text-brandColor transition-all hover:underline"
                                >
                                    @lang('admin::app.components.layouts.header.mega-search.explore-all-students')
                                </a>
                            </div>
                        </template>
                    </template>

                    <template v-if="activeTab == 'settings'">
                        <template v-if="isLoading">
                            <x-admin::shimmer.header.mega-search.settings />
                        </template>

                        <template v-else>
                            <div class="grid max-h-[400px] overflow-y-auto">
                                <template v-for="setting in searchedResults.settings">
                                    <a
                                        :href="setting.url"
                                        class="flex cursor-pointer justify-between gap-2.5 border-b border-slate-300 p-4 last:border-b-0 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-950"
                                    >
                                        <!-- Left Information -->
                                        <div class="flex gap-2.5">
                                            <!-- Details -->
                                            <div class="grid place-content-start gap-1.5">
                                                <p class="text-gray-600 dark:text-gray-300">
                                                    @{{ setting.name }}
                                                </p>
                                            </div>
                                        </div>
                                    </a>
                                </template>
                            </div>

                            <template v-if="! searchedResults.settings.length">
                                <div class="flex border-t p-3 dark:border-gray-800">
                                    <a
                                        href="{{ route('admin.settings.index') }}"
                                        class="cursor-pointer text-xs font-semibold text-brandColor transition-all hover:underline"
                                    >
                                        @lang('admin::app.components.layouts.header.mega-search.explore-all-settings')
                                    </a>
                                </div>
                            </template>
                        </template>
                    </template>

                    <template v-if="activeTab == 'configurations'">
                        <template v-if="isLoading">
                            <x-admin::shimmer.header.mega-search.configurations />
                        </template>

                        <template v-else>
                            <div class="grid max-h-[400px] overflow-y-auto">
                                <template v-for="configuration in searchedResults.configurations">
                                    <a
                                        :href="configuration.url"
                                        class="flex cursor-pointer justify-between gap-2.5 border-b border-slate-300 p-4 last:border-b-0 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-950"
                                    >
                                        <!-- Left Information -->
                                        <div class="flex gap-2.5">
                                            <!-- Details -->
                                            <div class="grid place-content-start gap-1.5">
                                                <p class="text-gray-600 dark:text-gray-300">
                                                    @{{ configuration.title }}
                                                </p>
                                            </div>
                                        </div>
                                    </a>
                                </template>
                            </div>

                            <template v-if="! searchedResults.configurations.length">
                                <div class="flex border-t p-3 dark:border-gray-800">
                                    <a
                                        href="{{ route('admin.configuration.index') }}"
                                        class="cursor-pointer text-xs font-semibold text-brandColor transition-all hover:underline"
                                    >
                                        @lang('admin::app.components.layouts.header.mega-search.explore-all-configurations')
                                    </a>
                                </div>
                            </template>
                        </template>
                    </template>
                </div>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-mobile-mega-search', {
            template: '#v-mobile-mega-search-template',

            data() {
                return  {
                    activeTab: 'events',

                    isSearchVisible: false,

                    isDropdownOpen: false,

                    tabs: {
                        events: {
                            key: 'events',
                            title: "@lang('admin::app.components.layouts.header.mega-search.tabs.events')",
                            is_active: false,
                            endpoint: "{{ route('admin.events.search') }}",
                            query: '',
                        },

                        students: {
                            key: 'students',
                            title: "@lang('admin::app.components.layouts.header.mega-search.tabs.students')",
                            is_active: false,
                            endpoint: "{{ route('admin.students.search') }}",
                            query: '',
                        },

                        settings: {
                            key: 'settings',
                            title: "@lang('admin::app.components.layouts.header.mega-search.tabs.settings')",
                            is_active: false,
                            endpoint: "{{ route('admin.settings.search') }}",
                            query: '',
                        },

                        configurations: {
                            key: 'configurations',
                            title: "@lang('admin::app.components.layouts.header.mega-search.tabs.configurations')",
                            is_active: false,
                            endpoint: "{{ route('admin.configuration.search') }}",
                            query: '',
                        },
                    },

                    isLoading: false,

                    searchTerm: '',

                    searchedResults: {
                        settings: [],
                        configurations: [],
                        students: [],
                        events: [],
                    },

                    params: {
                        search: '',
                        searchFields: '',
                    },
                };
            },

            watch: {
                searchTerm: 'updateSearchParams',

                activeTab: 'updateSearchParams',
            },

            created() {
                window.addEventListener('click', this.handleFocusOut);
            },

            beforeDestroy() {
                window.removeEventListener('click', this.handleFocusOut);
            },

            methods: {
                toggleSearchInput() {
                    this.isSearchVisible = ! this.isSearchVisible;
                    this.isDropdownOpen = false;

                    if (this.isSearchVisible) {
                        this.$nextTick(() => {
                            if (this.$refs.searchInput) {
                                this.$refs.searchInput.focus();
                            }
                        });
                    } else {
                        this.searchTerm = '';
                    }
                },

                search(endpoint) {
                    if (! endpoint) {
                        return;
                    }

                    if (this.searchTerm.length <= 1) {
                        this.searchedResults[this.activeTab] = [];

                        this.isDropdownOpen = false;

                        return;
                    }

                    this.isDropdownOpen = true;

                    this.$axios.get(endpoint, {
                            params: {
                                ...this.params,
                            },
                        })
                        .then((response) => {
                            this.searchedResults[this.activeTab] = response.data.data;
                        })
                        .catch((error) => {})
                        .finally(() => this.isLoading = false);
                },

                handleFocusOut(e) {
                    if (! this.$el.contains(e.target) || e.target.classList.contains('icon-cross-large')) {
                        this.isDropdownOpen = false;

                        if (! this.isDropdownOpen) {
                            this.isSearchVisible = false;
                            this.searchTerm = '';
                        }
                    }
                },

                updateSearchParams() {
                    const newTerm = this.searchTerm;

                    this.params = {
                        search: '',
                        searchFields: '',
                    };

                    const tab = this.tabs[this.activeTab];

                    if (
                        tab.key === 'settings'
                        || tab.key === 'configurations'
                        || tab.key === 'students'
                        || tab.key === 'events'
                    ) {
                        this.params = null;

                        this.search(`${tab.endpoint}?query=${newTerm}`);

                        return;
                    }

                    this.params.search += tab.query_params.map((param) => `${param.search}:${newTerm};`).join('');

                    this.params.searchFields += tab.query_params.map((param) => `${param.searchFields};`).join('');

                    this.search(tab.endpoint);
                },
            },
        });
    </script>
@endPushOnce
