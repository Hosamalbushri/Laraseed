{!! view_render_event('admin.dashboard.index.revenue_by_sources.before') !!}

<v-dashboard-events-status-distribution>
    <x-admin::shimmer.dashboard.index.revenue-by-sources />
</v-dashboard-events-status-distribution>

{!! view_render_event('admin.dashboard.index.revenue_by_sources.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-events-status-distribution-template"
    >
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.index.revenue-by-sources />
        </template>

        <template v-else>
            <div class="grid gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-col justify-between gap-1">
                    <p class="text-base font-semibold dark:text-gray-300">
                        @lang('admin::app.dashboard.index.events-status-distribution.title')
                    </p>
                </div>

                <div
                    class="flex w-full max-w-full flex-col gap-4 px-8 pt-8"
                    v-if="report.statistics.length"
                >
                    <x-admin::charts.doughnut
                        ::labels="chartLabels"
                        ::datasets="chartDatasets"
                    />

                    <div class="flex flex-wrap justify-center gap-5">
                        <div
                            class="flex items-center gap-2 whitespace-nowrap"
                            v-for="(stat, index) in report.statistics"
                            :key="stat.name"
                        >
                            <span
                                class="h-3.5 w-3.5 rounded-sm"
                                :style="{ backgroundColor: colors[index] }"
                            ></span>

                            <p class="text-xs dark:text-gray-300">
                                @{{ stat.name }}
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="flex flex-col gap-8 p-4"
                    v-else
                >
                    <div class="grid justify-center justify-items-center gap-3.5 py-2.5">
                        <img
                            src="{{ vite()->asset('images/empty-placeholders/default.svg') }}"
                            class="dark:mix-blend-exclusion dark:invert"
                        >

                        <div class="flex flex-col items-center">
                            <p class="text-base font-semibold text-gray-400">
                                @lang('admin::app.dashboard.index.events-status-distribution.empty-title')
                            </p>

                            <p class="text-gray-400">
                                @lang('admin::app.dashboard.index.events-status-distribution.empty-info')
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-events-status-distribution', {
            template: '#v-dashboard-events-status-distribution-template',

            data() {
                return {
                    report: [],

                    colors: [
                        '#8979FF',
                        '#FF928A',
                    ],

                    isLoading: true,
                }
            },

            computed: {
                chartLabels() {
                    return this.report.statistics.map(({ name }) => name);
                },

                chartDatasets() {
                    return [{
                        data: this.report.statistics.map(({ total }) => total),
                        backgroundColor: this.colors,
                    }];
                }
            },

            mounted() {
                this.getStats({});

                this.$emitter.on('reporting-filter-updated', this.getStats);
            },

            methods: {
                getStats(filters) {
                    this.isLoading = true;

                    filters = Object.assign({}, filters);
                    filters.type = 'events-status-distribution';

                    this.$axios.get("{{ route('admin.dashboard.stats') }}", {
                            params: filters
                        })
                        .then(response => {
                            this.report = response.data;
                            this.isLoading = false;
                        })
                        .catch(() => {
                            this.isLoading = false;
                        });
                },
            }
        });
    </script>
@endPushOnce
