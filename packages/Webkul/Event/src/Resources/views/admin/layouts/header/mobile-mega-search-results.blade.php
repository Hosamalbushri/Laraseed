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
                @lang('event::app.events.title')
            </a>
        </div>
    </template>
</template>
