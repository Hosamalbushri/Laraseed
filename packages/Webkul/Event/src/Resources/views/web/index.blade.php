@extends('web::layouts.master')

@section('content')
    <div class="web-container" data-event-web-index>
        <header class="web-home__empty">
            <p class="web-home__eyebrow">{{ trans('event::web.navigation.events') }}</p>
            <h1 class="web-home__heading">{{ trans('event::web.index.heading') }}</h1>
            <p class="web-home__summary">{{ trans('event::web.index.description') }}</p>
        </header>

        @if ($events->isEmpty())
            <x-web::alert variant="info" :title="trans('event::web.index.empty_title')">
                {{ trans('event::web.index.empty_body') }}
            </x-web::alert>
        @else
            <div class="web-home__sections">
                @foreach ($events as $event)
                    <x-web::card as="article" data-event-card>
                        @if ($event->image)
                            <img
                                src="{{ Storage::disk('public')->url($event->image) }}"
                                alt="{{ $event->title }}"
                                loading="lazy"
                            >
                        @endif

                        <x-web::card.header>
                            <h2>{{ $event->title }}</h2>
                        </x-web::card.header>

                        <x-web::card.content>
                            @if ($event->event_date)
                                <p>
                                    <x-web::badge variant="primary">
                                        {{ trans('event::web.fields.starts') }}
                                        <time datetime="{{ $event->event_date->toDateString() }}">{{ $event->event_date->format('Y-m-d') }}</time>
                                    </x-web::badge>
                                </p>
                            @endif

                            @if ($event->organizer)
                                <p><strong>{{ trans('event::web.fields.organizer') }}:</strong> {{ $event->organizer }}</p>
                            @endif

                            @if ($event->description)
                                <p>{{ \Illuminate\Support\Str::limit($event->description, 220) }}</p>
                            @endif
                        </x-web::card.content>

                        <x-web::card.footer>
                            <x-web::button
                                :href="route('event.web.show', $event->getKey())"
                                variant="outline"
                                size="sm"
                            >
                                {{ trans('event::web.index.view_details') }}
                            </x-web::button>
                        </x-web::card.footer>
                    </x-web::card>
                @endforeach
            </div>

            @if ($events->hasPages())
                <nav aria-label="{{ trans('event::web.pagination.label') }}">
                    @if ($events->previousPageUrl())
                        <x-web::button :href="$events->previousPageUrl()" variant="outline" size="sm">
                            {{ trans('event::web.pagination.previous') }}
                        </x-web::button>
                    @endif

                    <span aria-current="page">
                        {{ trans('event::web.pagination.status', [
                            'current' => $events->currentPage(),
                            'last' => $events->lastPage(),
                        ]) }}
                    </span>

                    @if ($events->nextPageUrl())
                        <x-web::button :href="$events->nextPageUrl()" variant="outline" size="sm">
                            {{ trans('event::web.pagination.next') }}
                        </x-web::button>
                    @endif
                </nav>
            @endif
        @endif
    </div>
@endsection
