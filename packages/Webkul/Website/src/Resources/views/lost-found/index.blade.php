@extends('web::layouts.master')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-7xl">
    {{-- Header Banner --}}
    <div class="mb-8">
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 mb-2">
            {{ trans('website::app.lost_found.badge') }}
        </span>
        <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900 tracking-tight">
            {{ trans('website::app.lost_found.search_heading') }}
        </h1>
        <p class="text-gray-600 text-base mt-2 max-w-2xl">
            {{ trans('website::app.lost_found.search_intro') }}
        </p>
    </div>

    {{-- Search & Filter Bar --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4 md:p-6 mb-8">
        <form method="GET" action="{{ route('website.lost_found.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
            {{-- Search query input --}}
            <div class="md:col-span-6">
                <label for="search-query" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                    {{ trans('website::app.lost_found.search_placeholder') }}
                </label>
                <div class="relative">
                    <input
                        type="text"
                        name="q"
                        id="search-query"
                        value="{{ $criteria->query }}"
                        placeholder="{{ trans('website::app.lost_found.search_placeholder') }}"
                        maxlength="100"
                        class="w-full rounded-xl border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500 py-2.5 px-3"
                    >
                </div>
            </div>

            {{-- Category dropdown --}}
            <div class="md:col-span-3">
                <label for="search-category" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                    {{ trans('website::app.lost_found.category') }}
                </label>
                <select
                    name="category"
                    id="search-category"
                    class="w-full rounded-xl border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500 py-2.5 px-3 bg-white"
                >
                    <option value="">{{ trans('website::app.lost_found.filter_all_cats') }}</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->code }}" {{ $criteria->category === $cat->code ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Action buttons --}}
            <div class="md:col-span-3 flex items-center gap-2">
                <button
                    type="submit"
                    class="flex-1 inline-flex justify-center items-center px-4 py-2.5 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                >
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    {{ trans('website::app.lost_found.search_button') }}
                </button>

                @if ($criteria->query !== null || $criteria->category !== null)
                    <a
                        href="{{ route('website.lost_found.index') }}"
                        class="inline-flex justify-center items-center px-3 py-2.5 border border-gray-300 rounded-xl shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none transition-colors"
                    >
                        {{ trans('website::app.lost_found.reset_button') }}
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Results Count --}}
    <div class="flex items-center justify-between mb-4">
        <span class="text-sm font-semibold text-gray-600">
            {{ trans('website::app.lost_found.results_count', ['count' => $results->total]) }}
        </span>
    </div>

    {{-- Items Grid or Empty State --}}
    @if ($results->isEmpty())
        <div class="bg-white rounded-2xl border border-dashed border-gray-300 p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            @if ($criteria->query !== null || $criteria->category !== null)
                <h3 class="text-lg font-bold text-gray-900 mb-1">
                    {{ trans('website::app.lost_found.no_results_title') }}
                </h3>
                <p class="text-sm text-gray-500 max-w-md mx-auto">
                    {{ trans('website::app.lost_found.no_results_desc') }}
                </p>
            @else
                <h3 class="text-lg font-bold text-gray-900 mb-1">
                    {{ trans('website::app.lost_found.empty_title') }}
                </h3>
                <p class="text-sm text-gray-500 max-w-md mx-auto">
                    {{ trans('website::app.lost_found.empty_desc') }}
                </p>
            @endif
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
            @foreach ($results->items as $item)
                <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between">
                    <div>
                        {{-- Image / Placeholder --}}
                        @if ($item->hasImage && $item->imageUrl)
                            <div class="aspect-video bg-gray-100 overflow-hidden">
                                <img src="{{ $item->imageUrl }}" alt="{{ $item->title }}" class="h-full w-full object-cover" loading="lazy">
                            </div>
                        @else
                            <div class="aspect-video bg-gray-50 flex items-center justify-center border-b border-gray-100">
                                <svg class="w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        @endif

                        <div class="p-4">
                            <div class="flex items-center justify-between text-xs mb-2">
                                <span class="font-mono font-semibold bg-gray-100 text-gray-700 px-2 py-0.5 rounded">
                                    {{ $item->reference }}
                                </span>
                                @if ($item->category)
                                    <span class="text-blue-600 font-medium">
                                        {{ $item->category }}
                                    </span>
                                @endif
                            </div>

                            <h2 class="text-base font-bold text-gray-900 line-clamp-1 mb-1">
                                {{ $item->title }}
                            </h2>

                            @if ($item->description)
                                <p class="text-xs text-gray-600 line-clamp-2 mb-3">
                                    {{ $item->description }}
                                </p>
                            @endif

                            <div class="space-y-1 text-xs text-gray-500 pt-2 border-t border-gray-100">
                                @if ($item->foundLocation)
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span class="truncate">{{ $item->foundLocation }}</span>
                                    </div>
                                @endif

                                @if ($item->foundAt)
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span>{{ $item->foundAt->format('Y-m-d') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="p-4 pt-0">
                        <a
                            href="{{ route('website.lost_found.show', ['reference' => $item->reference]) }}"
                            class="w-full inline-flex justify-center items-center px-3 py-2 border border-gray-200 rounded-xl text-xs font-semibold text-gray-700 bg-gray-50 hover:bg-gray-100 hover:text-blue-600 transition-colors"
                        >
                            {{ trans('website::app.lost_found.view_details') }}
                            <svg class="w-3.5 h-3.5 ltr:ml-1 rtl:mr-1 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if ($results->hasPages())
            <div class="flex items-center justify-between bg-white rounded-2xl border border-gray-200 p-4">
                <div>
                    @if ($results->previousPage())
                        <a
                            href="{{ route('website.lost_found.index', array_filter(['q' => $criteria->query, 'category' => $criteria->category, 'page' => $results->previousPage()])) }}"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-50"
                        >
                            ← {{ trans('website::app.lost_found.page_prev') }}
                        </a>
                    @endif
                </div>

                <span class="text-sm font-semibold text-gray-600">
                    {{ trans('website::app.lost_found.page_of', ['current' => $results->currentPage, 'last' => $results->lastPage]) }}
                </span>

                <div>
                    @if ($results->nextPage())
                        <a
                            href="{{ route('website.lost_found.index', array_filter(['q' => $criteria->query, 'category' => $criteria->category, 'page' => $results->nextPage()])) }}"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-50"
                        >
                            {{ trans('website::app.lost_found.page_next') }} →
                        </a>
                    @endif
                </div>
            </div>
        @endif
    @endif
</div>
@endsection
