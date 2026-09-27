@php
    $available = $event->isCurrentlyAvailable();
    $useSeats = $event->availability_use_seats;
    $seats = $event->available_seats;
@endphp
@if (! $available)
    @if ($event->availability_use_end_date && $event->event_end_date && \Carbon\Carbon::today()->gt(\Carbon\Carbon::parse($event->event_end_date)->startOfDay()))
        <span class="font-semibold text-[color:var(--shop-accent)]">{{ __('shop::app.events.seats.ended') }}</span>
    @elseif ($useSeats && $seats !== null && (int) $seats === 0)
        <span class="font-semibold text-[color:var(--shop-accent)]">{{ __('shop::app.events.seats.sold-out') }}</span>
    @else
        <span class="font-semibold text-[color:var(--shop-accent)]">{{ __('shop::app.events.seats.unavailable') }}</span>
    @endif
@elseif ($useSeats)
    @if ($seats === null)
        <span class="font-semibold text-[color:var(--shop-text)]">{{ __('shop::app.events.seats.unlimited') }}</span>
    @else
        <span class="font-semibold text-[color:var(--shop-text)]">{{ __('shop::app.events.seats.remaining', ['count' => (int) $seats]) }}</span>
    @endif
@else
    <span class="font-semibold text-[color:var(--shop-text)]">{{ __('shop::app.events.seats.open-registrations') }}</span>
@endif
