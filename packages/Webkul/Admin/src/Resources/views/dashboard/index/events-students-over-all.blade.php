{!! view_render_event('admin.dashboard.index.over_all.before') !!}

<v-dashboard-events-students-over-all>
    <x-admin::shimmer.dashboard.index.over-all />
</v-dashboard-events-students-over-all>

{!! view_render_event('admin.dashboard.index.over_all.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-events-students-over-all-template"
    >
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.index.over-all />
        </template>

        <template v-else>
            <div class="grid grid-cols-4 gap-4 max-2xl:grid-cols-3 max-md:grid-cols-2 max-sm:grid-cols-1">
                <div
                    class="flex flex-col gap-2 rounded-lg border border-gray-200 bg-white px-4 py-5 dark:border-gray-800 dark:bg-gray-900"
                    v-for="stat in stats"
                    :key="stat.key"
                >
                    <p class="text-xs font-medium text-gray-600 dark:text-gray-300">
                        @{{ stat.label }}
                    </p>

                    <div class="flex items-center gap-2">
                        <p class="text-xl font-bold dark:text-gray-300">
                            @{{ stat.value }}
                        </p>

                        <div class="flex items-center gap-0.5">
                            <span
                                class="text-base !font-semibold"
                                :class="[stat.progress < 0 ? 'icon-stats-down text-red-500 dark:!text-red-500' : 'icon-stats-up text-green-500 dark:!text-green-500']"
                            ></span>

                            <p
                                class="text-xs font-semibold"
                                :class="[stat.progress < 0 ?  'text-red-500' : 'text-green-500']"
                            >
                                @{{ Math.abs(stat.progress).toFixed(2) }}%
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-events-students-over-all', {
            template: '#v-dashboard-events-students-over-all-template',

            data() {
                return {
                    report: [],

                    isLoading: true,
                }
            },

            computed: {
                stats() {
                    const statistics = this.report.statistics || {};

                    return [
                        {
                            key: 'total_events',
                            label: "@lang('admin::app.dashboard.index.events-students-over-all.total-events')",
                            value: statistics.total_events?.current ?? 0,
                            progress: statistics.total_events?.progress ?? 0,
                        },
                        {
                            key: 'published_events',
                            label: "@lang('admin::app.dashboard.index.events-students-over-all.published-events')",
                            value: statistics.published_events?.current ?? 0,
                            progress: statistics.published_events?.progress ?? 0,
                        },
                        {
                            key: 'currently_available_events',
                            label: "@lang('admin::app.dashboard.index.events-students-over-all.currently-available-events')",
                            value: statistics.currently_available_events?.current ?? 0,
                            progress: statistics.currently_available_events?.progress ?? 0,
                        },
                        {
                            key: 'ending_soon_events',
                            label: "@lang('admin::app.dashboard.index.events-students-over-all.ending-soon-events')",
                            value: statistics.ending_soon_events?.current ?? 0,
                            progress: statistics.ending_soon_events?.progress ?? 0,
                        },
                        {
                            key: 'total_students',
                            label: "@lang('admin::app.dashboard.index.events-students-over-all.total-students')",
                            value: statistics.total_students?.current ?? 0,
                            progress: statistics.total_students?.progress ?? 0,
                        },
                        {
                            key: 'students_with_subscriptions',
                            label: "@lang('admin::app.dashboard.index.events-students-over-all.students-with-subscriptions')",
                            value: statistics.students_with_subscriptions?.current ?? 0,
                            progress: statistics.students_with_subscriptions?.progress ?? 0,
                        },
                        {
                            key: 'total_subscriptions',
                            label: "@lang('admin::app.dashboard.index.events-students-over-all.total-subscriptions')",
                            value: statistics.total_subscriptions?.current ?? 0,
                            progress: statistics.total_subscriptions?.progress ?? 0,
                        },
                    ];
                },
            },

            mounted() {
                this.getStats({});

                this.$emitter.on('reporting-filter-updated', this.getStats);
            },

            methods: {
                getStats(filters) {
                    this.isLoading = true;

                    filters = Object.assign({}, filters);
                    filters.type = 'events-students-over-all';

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
