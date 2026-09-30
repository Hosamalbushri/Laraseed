@extends('web::layouts.master')

@section('content')
    <div class="mx-auto w-full max-w-content px-4 py-10 sm:px-6 sm:py-12 lg:px-8">
        @if ($sections->isEmpty())
            <div class="mx-auto max-w-3xl py-12 text-center" data-web-home__empty>
                <p class="text-sm font-bold uppercase tracking-widest text-blue-700">{{ trans('web::app.home.title') }}</p>
                <h1 class="mt-3 text-3xl font-extrabold leading-tight text-slate-950 sm:text-5xl">{{ trans('web::app.home.heading') }}</h1>
                <p class="mx-auto mt-5 max-w-2xl text-lg leading-8 text-slate-600">{{ trans('web::app.home.subheading') }}</p>
            </div>
        @else
            <div class="space-y-8">
                @foreach ($sections as $section)
                    <section id="section-{{ $section['key'] }}">
                        @include($section['view'], $section['data'])
                    </section>
                @endforeach
            </div>
        @endif
    </div>
@endsection
