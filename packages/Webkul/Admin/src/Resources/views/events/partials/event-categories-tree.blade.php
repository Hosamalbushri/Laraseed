@php
    $selectedCategoryIds = array_values(array_map('intval', (array) ($selectedCategoryIds ?? [])));
@endphp

<div class="max-h-96 overflow-y-auto rounded-md border border-gray-200 bg-gray-50/50 p-3 dark:border-gray-700 dark:bg-gray-950/30">
    <v-event-categories-tree>
        <x-admin::shimmer.tree />
    </v-event-categories-tree>
</div>

@pushOnce('scripts', 'webkul-admin-v-event-categories-tree')
    <script
        type="text/x-template"
        id="v-event-categories-tree-template"
    >
        <div>
            <template v-if="isLoading">
                <x-admin::shimmer.tree />
            </template>

            <template v-else>
                {{-- name-field is decoy: real POST keys come from hidden inputs (vee-validate + render() trees often omit these from native submit) --}}
                <x-admin::tree.view
                    input-type="checkbox"
                    selection-type="individual"
                    name-field="event_category_tree_ui"
                    id-field="id"
                    value-field="id"
                    ::items="categories"
                    :value="json_encode($selectedCategoryIds)"
                    fallback-locale="{{ config('app.fallback_locale') }}"
                    v-on:change-input="onCategoriesInput"
                />
            </template>

            <template
                v-for="id in syncedCategoryIds"
                v-bind:key="'event-cat-hid-' + id"
            >
                <input
                    type="hidden"
                    name="category_ids[]"
                    v-bind:value="id"
                />
            </template>
        </div>
    </script>

    <script type="module">
        app.component('v-event-categories-tree', {
            template: '#v-event-categories-tree-template',

            data() {
                return {
                    isLoading: true,
                    categories: [],
                    syncedCategoryIds: {!! json_encode($selectedCategoryIds) !!},
                };
            },

            mounted() {
                this.get();
            },

            methods: {
                onCategoriesInput(values) {
                    if (!Array.isArray(values)) {
                        this.syncedCategoryIds = [];

                        return;
                    }

                    this.syncedCategoryIds = values
                        .map((v) => parseInt(v, 10))
                        .filter((n) => !Number.isNaN(n) && n > 0);
                },

                get() {
                    axios
                        .get("{{ route('admin.events.categories.tree') }}")
                        .then((response) => {
                            this.isLoading = false;
                            this.categories = response.data.data;
                        })
                        .catch(() => {
                            this.isLoading = false;
                        });
                },
            },
        });
    </script>
@endPushOnce
