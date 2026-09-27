@props([
    'for' => null,
])

<label
    @if ($for) for="{{ $for }}" @endif
    {{ $attributes->merge(['class' => 'mb-1 block text-sm font-medium text-[color:var(--shop-text-muted)]']) }}
>
    {{ $slot }}
</label>
