@extends('web::layouts.master')

@section('content')
    <div class="web-container" data-event-web-show>
        <p>
            <x-web::button :href="route('event.web.index')" variant="ghost" size="sm">
                {{ trans('event::web.show.back') }}
            </x-web::button>
        </p>

        <x-web::card as="article">
            <x-web::card.header>
                <h1>{{ $event->title }}</h1>
            </x-web::card.header>

            <x-web::card.content>
                @if ($event->images->isNotEmpty())
                    @foreach ($event->images as $image)
                        <img
                            src="{{ Storage::disk('public')->url($image->path) }}"
                            alt="{{ trans('event::web.fields.image_alt', [
                                'title' => $event->title,
                                'number' => $loop->iteration,
                            ]) }}"
                            @if (! $loop->first) loading="lazy" @endif
                        >
                    @endforeach
                @elseif ($event->image)
                    <img
                        src="{{ Storage::disk('public')->url($event->image) }}"
                        alt="{{ $event->title }}"
                    >
                @endif

                @if ($event->event_date)
                    <p>
                        <strong>{{ trans('event::web.fields.starts') }}:</strong>
                        <time datetime="{{ $event->event_date->toDateString() }}">{{ $event->event_date->format('Y-m-d') }}</time>
                    </p>
                @endif

                @if ($event->event_end_date)
                    <p>
                        <strong>{{ trans('event::web.fields.ends') }}:</strong>
                        <time datetime="{{ $event->event_end_date->toDateString() }}">{{ $event->event_end_date->format('Y-m-d') }}</time>
                    </p>
                @endif

                @if ($event->organizer)
                    <p><strong>{{ trans('event::web.fields.organizer') }}:</strong> {{ $event->organizer }}</p>
                @endif

                <p>
                    <strong>{{ trans('event::web.fields.seats') }}:</strong>
                    @if ($event->availability_use_seats && $event->available_seats !== null)
                        {{ $event->available_seats }}
                    @else
                        {{ trans('event::web.fields.unlimited') }}
                    @endif
                </p>

                @if ($event->description)
                    <p>{{ $event->description }}</p>
                @endif
            </x-web::card.content>
        </x-web::card>
    </div>
@endsection
