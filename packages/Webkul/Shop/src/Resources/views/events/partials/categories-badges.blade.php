@php
    $gapClass = $gapClass ?? 'gap-2';
    $categories = $event->categories ?? collect();
@endphp
@if ($categories->isNotEmpty())
    <div class="flex flex-wrap items-center {{ $gapClass }}">
        @foreach ($categories as $cat)
            <span class="inline-flex rounded-full bg-white px-3 py-0.5 text-xs font-semibold uppercase tracking-wide text-[color:var(--shop-accent)] shadow-sm ring-1 ring-inset ring-[color:var(--shop-border-soft)] dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-600">
                {{ $cat->name }}
            </span>
        @endforeach
    </div>
@endif
