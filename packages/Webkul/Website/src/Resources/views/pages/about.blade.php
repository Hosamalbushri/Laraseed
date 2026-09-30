@extends('web::layouts.master')

@section('content')
<div class="container mx-auto px-4 py-12 max-w-4xl">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 md:p-12">
        @if ($siteDefinition->tagline)
            <span class="inline-block px-3 py-1 mb-3 text-xs font-semibold tracking-wider text-blue-700 uppercase bg-blue-50 rounded-full">
                {{ $siteDefinition->tagline }}
            </span>
        @endif

        <p class="text-sm font-semibold text-blue-600 mb-1">
            {{ $siteDefinition->name }}
        </p>

        <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-4">
            {{ $siteDefinition->aboutHeading ?? trans('website::app.about.heading') }}
        </h1>

        <p class="text-gray-600 text-lg leading-relaxed mb-8">
            {{ $siteDefinition->aboutBody ?? trans('website::app.about.body') }}
        </p>

        @if ($siteDefinition->hasContact())
            <div class="website-about-contact border-t border-gray-100 pt-6 mb-8">
                <h2 class="text-lg font-bold text-gray-900 mb-4">
                    {{ trans('website::app.contact.heading') }}
                </h2>

                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    @if ($siteDefinition->address)
                        <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                            <dt class="font-semibold text-gray-700 mb-1">{{ trans('website::app.contact.address') }}</dt>
                            <dd class="text-gray-600 m-0">{{ $siteDefinition->address }}</dd>
                        </div>
                    @endif

                    @if ($siteDefinition->email)
                        <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                            <dt class="font-semibold text-gray-700 mb-1">{{ trans('website::app.contact.email') }}</dt>
                            <dd class="text-gray-600 m-0">
                                <a href="mailto:{{ $siteDefinition->email }}" class="text-blue-600 hover:underline">
                                    {{ $siteDefinition->email }}
                                </a>
                            </dd>
                        </div>
                    @endif

                    @if ($siteDefinition->phone)
                        <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                            <dt class="font-semibold text-gray-700 mb-1">{{ trans('website::app.contact.phone') }}</dt>
                            <dd class="text-gray-600 m-0" dir="ltr">{{ $siteDefinition->phone }}</dd>
                        </div>
                    @endif

                    @if ($siteDefinition->officeHours)
                        <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                            <dt class="font-semibold text-gray-700 mb-1">{{ trans('website::app.contact.office_hours') }}</dt>
                            <dd class="text-gray-600 m-0">{{ $siteDefinition->officeHours }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        @endif

        <div class="border-t border-gray-100 pt-6">
            <a href="{{ url('/') }}" class="text-blue-600 font-semibold hover:underline inline-flex items-center gap-2">
                <span class="rtl:rotate-180">←</span> {{ trans('website::app.nav.home') }}
            </a>
        </div>
    </div>
</div>
@endsection
