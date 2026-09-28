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
            <template v-if="searchedResults.students && searchedResults.students.length">
                <a
                    :href="'{{ route('admin.students.index') }}?search=:query'.replace(':query', searchTerm)"
                    class="cursor-pointer text-xs font-semibold text-brandColor transition-all hover:underline"
                >
                    @lang('student::app.components.layouts.header.mega-search.explore-all-students')
                </a>
            </template>

            <template v-else>
                <a
                    href="{{ route('admin.students.index') }}"
                    class="cursor-pointer text-xs font-semibold text-brandColor transition-all hover:underline"
                >
                    @lang('student::app.components.layouts.header.mega-search.explore-all-students')
                </a>
            </template>
        </div>
    </template>
</template>
