<div class="website-lost-found py-6 bg-white rounded-2xl p-6 md:p-8 border border-gray-200">
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-3">
        <div>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 mb-2">
                {{ trans('website::app.lost_found.badge') }}
            </span>
            <h2 class="text-2xl font-bold text-gray-900 mb-1">
                {{ trans('website::app.lost_found.heading') }}
            </h2>
            <p class="text-gray-600 text-sm">
                {{ trans('website::app.lost_found.subheading') }}
            </p>
        </div>

        <div>
            <a
                href="{{ route('website.lost_found.index') }}"
                class="inline-flex items-center text-sm font-semibold text-blue-600 hover:text-blue-800 transition-colors"
            >
                {{ trans('website::app.lost_found.browse_all') }}
                <svg class="w-4 h-4 ltr:ml-1 rtl:mr-1 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
    </div>

    @if (empty($recentItems))
        <div class="website-lost-found__empty p-8 bg-gray-50 rounded-xl border border-dashed border-gray-300 text-center">
            <svg class="mx-auto h-10 w-10 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
            <h4 class="font-semibold text-gray-800 text-base">{{ trans('website::app.lost_found.empty_title') }}</h4>
            <p class="text-gray-500 text-sm mt-1 max-w-md mx-auto">{{ trans('website::app.lost_found.empty_desc') }}</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($recentItems as $item)
                <div class="website-lost-found-card p-4 bg-gray-50 rounded-xl border border-gray-200 flex flex-col justify-between hover:shadow-sm transition-shadow">
                    <div>
                        @if ($item->hasImage && $item->imageUrl)
                            <div class="mb-3 overflow-hidden rounded-lg bg-gray-100 aspect-video flex items-center justify-center">
                                <img src="{{ $item->imageUrl }}" alt="{{ $item->title }}" class="h-full w-full object-cover" loading="lazy">
                            </div>
                        @endif

                        <div class="flex items-center justify-between text-xs text-gray-500 mb-2">
                            <span class="font-mono bg-gray-200 text-gray-700 px-2 py-0.5 rounded font-semibold">{{ $item->reference }}</span>
                            @if ($item->category)
                                <span class="text-gray-600 font-medium">{{ $item->category }}</span>
                            @endif
                        </div>

                        <a href="{{ route('website.lost_found.show', ['reference' => $item->reference]) }}" class="hover:text-blue-600 transition-colors">
                            <h4 class="font-bold text-gray-900 text-base mb-1">{{ $item->title }}</h4>
                        </a>

                        @if ($item->description)
                            <p class="text-gray-600 text-sm mb-3 line-clamp-2">{{ $item->description }}</p>
                        @endif
                    </div>

                    <div class="pt-3 border-t border-gray-200 text-xs text-gray-500 flex flex-col gap-1">
                        @if ($item->foundLocation)
                            <div class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>{{ $item->foundLocation }}</span>
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
            @endforeach
        </div>
    @endif
</div>
