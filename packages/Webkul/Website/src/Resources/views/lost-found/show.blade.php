@extends('web::layouts.master')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-5xl">
    {{-- Back Link --}}
    <div class="mb-6">
        <a
            href="{{ route('website.lost_found.index') }}"
            class="inline-flex items-center text-sm font-semibold text-blue-600 hover:text-blue-800 transition-colors"
        >
            <span class="ltr:mr-1 rtl:ml-1 rtl:rotate-180">←</span>
            {{ trans('website::app.lost_found.back_to_search') }}
        </a>
    </div>

    {{-- Main Detail Card --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-8">
        <div class="p-6 md:p-8">
            {{-- Reference & Category Header --}}
            <div class="flex flex-wrap items-center justify-between gap-3 pb-6 border-b border-gray-100">
                <div class="flex items-center gap-2">
                    <span class="font-mono text-sm font-bold bg-gray-100 text-gray-800 px-3 py-1 rounded-lg border border-gray-200">
                        {{ $item->reference }}
                    </span>
                    @if ($item->category)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100">
                            {{ $item->category }}
                        </span>
                    @endif
                </div>

                @if ($item->foundAt)
                    <div class="flex items-center text-sm text-gray-500 gap-1.5">
                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>{{ trans('website::app.lost_found.date') }}: {{ $item->foundAt->format('Y-m-d') }}</span>
                    </div>
                @endif
            </div>

            {{-- Title & Found Location --}}
            <div class="py-6">
                <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 mb-3">
                    {{ $item->title }}
                </h1>

                @if ($item->foundLocation)
                    <div class="flex items-center text-sm text-gray-600 gap-2 mb-4">
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="font-medium">{{ trans('website::app.lost_found.location') }}:</span>
                        <span>{{ $item->foundLocation }}</span>
                    </div>
                @endif

                @if ($item->description)
                    <div class="mt-4">
                        <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                            {{ trans('website::app.lost_found.item_details') }}
                        </h2>
                        <p class="text-gray-700 text-base leading-relaxed bg-gray-50 p-4 rounded-xl border border-gray-100">
                            {{ $item->description }}
                        </p>
                    </div>
                @endif
            </div>

            {{-- Image Gallery --}}
            @php
                $allImages = !empty($item->additionalImages)
                    ? $item->additionalImages
                    : ($item->imageUrl ? [$item->imageUrl] : []);
            @endphp

            @if (!empty($allImages))
                <div class="pt-6 border-t border-gray-100">
                    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                        {{ trans('website::app.lost_found.item_gallery') }}
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        @foreach ($allImages as $photoUrl)
                            <div class="aspect-video rounded-xl overflow-hidden bg-gray-100 border border-gray-200">
                                <img src="{{ $photoUrl }}" alt="{{ $item->title }}" class="h-full w-full object-cover" loading="lazy">
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- How to Claim Banner --}}
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl border border-blue-200 p-6 md:p-8">
        <div class="flex flex-col md:flex-row md:items-start justify-between gap-6">
            <div class="space-y-3 flex-1">
                <div class="flex items-center gap-2">
                    <span class="p-2 bg-blue-600 text-white rounded-lg">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </span>
                    <h2 class="text-xl font-bold text-gray-900">
                        {{ trans('website::app.lost_found.claim_heading') }}
                    </h2>
                </div>

                <p class="text-sm text-gray-700 leading-relaxed">
                    {{ trans('website::app.lost_found.claim_info') }}
                </p>

                <p class="text-xs text-gray-600 bg-white/70 p-3 rounded-lg border border-blue-100">
                    {{ trans('website::app.lost_found.reference_note', ['ref' => $item->reference]) }}
                </p>

                <p class="text-xs text-gray-500">
                    {{ trans('website::app.lost_found.security_office') }}
                </p>
            </div>

            <div class="md:self-center shrink-0">
                <a
                    href="{{ url('/student/login') }}"
                    class="inline-flex items-center justify-center px-6 py-3 border border-transparent rounded-xl shadow-sm text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 transition-colors"
                >
                    {{ trans('website::app.lost_found.claim_portal_btn') }}
                    <svg class="w-4 h-4 ltr:ml-2 rtl:mr-2 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
