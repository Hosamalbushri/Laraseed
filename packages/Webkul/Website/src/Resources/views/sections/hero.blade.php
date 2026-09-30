<div class="website-hero rounded-2xl bg-gradient-to-r from-blue-900 to-indigo-900 text-white p-8 md:p-12 shadow-xl my-6">
    <div class="max-w-3xl">
        @if ($siteDefinition->tagline)
            <span class="inline-block px-3 py-1 mb-4 text-xs font-semibold tracking-wider text-blue-200 uppercase bg-blue-800 bg-opacity-60 rounded-full">
                {{ $siteDefinition->tagline }}
            </span>
        @endif

        <h1 class="text-3xl md:text-5xl font-extrabold tracking-tight mb-4 leading-tight">
            {{ trans('website::app.hero.welcome_title', ['name' => $siteDefinition->name]) }}
        </h1>

        @if ($siteDefinition->description)
            <p class="text-lg md:text-xl text-blue-100 mb-8 leading-relaxed">
                {{ $siteDefinition->description }}
            </p>
        @endif

        <div class="flex flex-wrap gap-4">
            <a href="{{ url('/about') }}" class="px-6 py-3 rounded-lg font-semibold bg-white text-blue-900 hover:bg-blue-50 transition shadow">
                {{ trans('website::app.hero.explore_button') }}
            </a>
            <a href="{{ url('/student/login') }}" class="px-6 py-3 rounded-lg font-semibold bg-blue-700 text-white hover:bg-blue-600 transition shadow border border-blue-500">
                {{ trans('website::app.hero.portal_button') }}
            </a>
        </div>
    </div>
</div>
