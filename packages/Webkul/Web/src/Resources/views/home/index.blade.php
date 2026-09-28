@extends('web::layouts.master')

@section('content')
<div class="container mx-auto px-4 py-8">
    @if ($sections->isEmpty())
        <div class="text-center py-16">
            <h1 class="text-3xl font-bold text-gray-800 mb-4">{{ trans('web::app.home.heading') }}</h1>
            <p class="text-gray-600 text-lg">{{ trans('web::app.home.subheading') }}</p>
        </div>
    @else
        <div class="space-y-8">
            @foreach ($sections as $section)
                <section id="section-{{ $section['key'] }}" class="web-section">
                    @include($section['view'], $section['data'])
                </section>
            @endforeach
        </div>
    @endif
</div>
@endsection
