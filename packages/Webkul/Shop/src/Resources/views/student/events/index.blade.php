<x-shop::layouts :title="__('shop::app.student.events.title')">
    <div class="container px-4 py-10 max-md:px-4 lg:px-[60px]">
        <div class="mb-8">
            <h1 class="text-3xl font-bold tracking-tight text-[color:var(--shop-text)] sm:text-4xl">
                {{ __('shop::app.student.events.heading') }}
            </h1>
            <p class="mt-2 max-w-2xl text-[color:var(--shop-text-muted)]">
                {{ __('shop::app.student.events.subheading') }}
            </p>
        </div>

        @if ($events->isEmpty())
            <div class="rounded-2xl border border-dashed border-[color:var(--shop-border-soft)] bg-white px-6 py-16 text-center">
                <p class="text-[color:var(--shop-text-muted)]">{{ __('shop::app.student.events.empty') }}</p>
            </div>
        @else
            <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 lg:gap-8">
                @foreach ($events as $event)
                    <li class="min-w-0">
                        @include('shop::events.partials.event-card', [
                            'event' => $event,
                            'variant' => 'grid',
                            'showExcerpt' => true,
                            'subscribedEventIds' => $subscribedEventIds ?? [],
                            'allowUnsubscribe' => true,
                        ])
                    </li>
                @endforeach
            </ul>

            <div class="mt-8">
                {{ $events->links() }}
            </div>
        @endif
    </div>
</x-shop::layouts>

