@extends('web::layouts.master')

@section('content')
    <div class="web-container web-home">
        @if ($sections->isEmpty())
            <div class="web-home__empty">
                <p class="web-home__eyebrow">{{ trans('web::app.home.title') }}</p>
                <h1 class="web-home__heading">{{ trans('web::app.home.heading') }}</h1>
                <p class="web-home__summary">{{ trans('web::app.home.subheading') }}</p>
            </div>
        @else
            <div class="web-home__sections">
                @foreach ($sections as $section)
                    <section id="section-{{ $section['key'] }}" class="web-section">
                        @include($section['view'], $section['data'])
                    </section>
                @endforeach
            </div>
        @endif
    </div>
@endsection
