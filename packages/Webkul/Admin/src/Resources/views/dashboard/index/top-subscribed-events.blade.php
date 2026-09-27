{!! view_render_event('admin.dashboard.index.top_subscribed_events.before') !!}

<v-dashboard-top-subscribed-events>
    <div class="grid gap-3 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <div class="light-shimmer-bg dark:shimmer h-6 w-44 rounded"></div>
        <div class="light-shimmer-bg dark:shimmer h-8 w-full rounded"></div>
        <div class="light-shimmer-bg dark:shimmer h-8 w-full rounded"></div>
        <div class="light-shimmer-bg dark:shimmer h-8 w-full rounded"></div>
    </div>
</v-dashboard-top-subscribed-events>

{!! view_render_event('admin.dashboard.index.top_subscribed_events.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-top-subscribed-events-template"
    >
        <div class="grid gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-col justify-between gap-1">
                <p class="text-base font-semibold dark:text-gray-300">
                    @lang('admin::app.dashboard.index.top-subscribed-events.title')
                </p>
            </div>

            <template v-if="isLoading">
                <div class="grid gap-2">
                    <div class="light-shimmer-bg dark:shimmer h-8 w-full rounded"></div>
                    <div class="light-shimmer-bg dark:shimmer h-8 w-full rounded"></div>
                    <div class="light-shimmer-bg dark:shimmer h-8 w-full rounded"></div>
                </div>
            </template>

            <template v-else-if="rows.length">
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-800">
                                <th class="px-2 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.dashboard.index.top-subscribed-events.rank')
                                </th>
                                <th class="px-2 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.dashboard.index.top-subscribed-events.event')
                                </th>
                                <th class="px-2 py-2 text-right text-xs font-semibold text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.dashboard.index.top-subscribed-events.subscriptions')
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                class="border-b border-gray-100 dark:border-gray-800"
                                v-for="(row, index) in rows"
                                :key="row.event_id"
                            >
                                <td class="px-2 py-2 text-xs text-gray-600 dark:text-gray-300">
                                    @{{ index + 1 }}
                                </td>
                                <td class="max-w-[180px] truncate px-2 py-2 text-xs font-medium text-gray-700 dark:text-gray-200">
                                    @{{ row.event_name }}
                                </td>
                                <td class="px-2 py-2 text-right text-xs font-semibold text-gray-700 dark:text-gray-200">
                                    @{{ row.subscriptions_count }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>

            <template v-else>
                <div class="grid justify-center justify-items-center gap-3 py-2.5">
                    <img
                        src="{{ vite()->asset('images/empty-placeholders/default.svg') }}"
                        class="h-20 w-20 dark:mix-blend-exclusion dark:invert"
                    >

                    <div class="flex flex-col items-center">
                        <p class="text-sm font-semibold text-gray-400">
                            @lang('admin::app.dashboard.index.top-subscribed-events.empty-title')
                        </p>

                        <p class="text-xs text-gray-400">
                            @lang('admin::app.dashboard.index.top-subscribed-events.empty-info')
                        </p>
                    </div>
                </div>
            </template>
        </div>
    </script>

    <script type="module">
        app.component('v-dashboard-top-subscribed-events', {
            template: '#v-dashboard-top-subscribed-events-template',

            data() {
                return {
                    report: [],
                    isLoading: true,
                }
            },

            computed: {
                rows() {
                    return this.report.statistics ?? [];
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
                    filters.type = 'top-subscribed-events';

                    this.$axios.get("{{ route('admin.dashboard.stats') }}", {
                            params: filters
                        })
                        .then((response) => {
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
