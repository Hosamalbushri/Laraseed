@props([
    'id' => null,
    'type' => 'text',
    'name' => null,
    'value' => null,
])

<input
    @if ($id) id="{{ $id }}" @endif
    type="{{ $type }}"
    @if ($name) name="{{ $name }}" @endif
    @if (! is_null($value)) value="{{ $value }}" @endif
    {{ $attributes->merge(['class' => 'w-full rounded-lg border border-[color:var(--shop-border-soft)] bg-white px-3 py-2 text-sm text-[color:var(--shop-text)] transition placeholder:text-slate-400 hover:border-[color:var(--shop-border-hover)] focus:border-[color:var(--shop-accent)] focus:ring-2 focus:ring-[color:var(--shop-accent)]/20 focus:outline-none disabled:cursor-not-allowed disabled:border-[color:var(--shop-border-soft)] disabled:bg-[color:var(--shop-surface)] disabled:text-[color:var(--shop-text-muted)] disabled:opacity-80']) }}
>
