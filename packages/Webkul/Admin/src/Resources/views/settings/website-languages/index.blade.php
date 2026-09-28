<x-admin::layouts>
    <x-slot:title>@lang('admin::website-languages.title')</x-slot>

    <div class="mb-6 rounded-lg bg-white p-5 dark:bg-gray-900">
        <h1 class="text-xl font-bold dark:text-white">@lang('admin::website-languages.title')</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">@lang('admin::website-languages.info')</p>
        <p class="mt-2 text-sm dark:text-gray-300">@lang('admin::website-languages.primary'): {{ $primary->name }} ({{ $primary->code }})</p>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">@lang('admin::website-languages.separate')</p>
    </div>

    @if ($errors->any())
        <div role="alert" class="mb-4 rounded bg-red-100 p-4 text-red-800">{{ $errors->first() }}</div>
    @endif

    @if (session('success'))
        <div role="status" class="mb-4 rounded bg-green-100 p-4 text-green-800">{{ session('success') }}</div>
    @endif

    @if (bouncer()->hasPermission('settings.website_languages.create'))
        <form method="POST" action="{{ route('admin.settings.website-languages.store') }}" class="mb-6 grid gap-3 rounded-lg bg-white p-5 dark:bg-gray-900">
            @csrf
            <h2 class="font-semibold dark:text-white">@lang('admin::website-languages.add')</h2>
            <label class="dark:text-white">@lang('admin::website-languages.code') <input name="code" value="{{ old('code') }}" required class="w-full rounded border p-2 text-gray-900"></label>
            <label class="dark:text-white">@lang('admin::website-languages.name') <input name="name" value="{{ old('name') }}" required class="w-full rounded border p-2 text-gray-900"></label>
            <label class="dark:text-white">@lang('admin::website-languages.direction')
                <select name="direction" class="w-full rounded border p-2 text-gray-900"><option value="ltr">LTR</option><option value="rtl">RTL</option></select>
            </label>
            <label class="dark:text-white">@lang('admin::website-languages.order') <input type="number" min="0" name="sort_order" value="{{ old('sort_order', 0) }}" required class="w-full rounded border p-2 text-gray-900"></label>
            <button class="primary-button" type="submit">@lang('admin::website-languages.add')</button>
        </form>
    @endif

    <div class="grid gap-4">
        @foreach ($languages as $language)
            <div class="rounded-lg bg-white p-5 dark:bg-gray-900 dark:text-white">
                <div class="mb-3 font-semibold">{{ $language->name }} ({{ $language->code }})
                    @if ($language->id === $primary->id) <span class="text-green-700">@lang('admin::website-languages.primary')</span> @endif
                    <span class="text-sm">— {{ $language->is_active ? trans('admin::website-languages.active') : trans('admin::website-languages.inactive') }}</span>
                </div>
                @if (bouncer()->hasPermission('settings.website_languages.edit'))
                    <form method="POST" action="{{ route('admin.settings.website-languages.update', $language->id) }}" class="grid gap-2">
                        @csrf @method('PUT')
                        <label>@lang('admin::website-languages.name') <input name="name" value="{{ $language->name }}" required class="w-full rounded border p-2 text-gray-900"></label>
                        <label>@lang('admin::website-languages.direction') <select name="direction" class="rounded border p-2 text-gray-900"><option value="ltr" @selected($language->direction->value === 'ltr')>LTR</option><option value="rtl" @selected($language->direction->value === 'rtl')>RTL</option></select></label>
                        <label>@lang('admin::website-languages.order') <input type="number" min="0" name="sort_order" value="{{ $language->sort_order }}" required class="rounded border p-2 text-gray-900"></label>
                        <button class="secondary-button" type="submit">@lang('admin::website-languages.save')</button>
                    </form>
                @endif
                @if (bouncer()->hasPermission('settings.website_languages.manage'))
                    <div class="mt-3 flex gap-3">
                        @if (! $language->is_active)
                            <form method="POST" action="{{ route('admin.settings.website-languages.activate', $language->id) }}">@csrf <button class="secondary-button">@lang('admin::website-languages.activate')</button></form>
                        @elseif ($language->id !== $primary->id)
                            <form method="POST" action="{{ route('admin.settings.website-languages.deactivate', $language->id) }}">@csrf <button class="secondary-button">@lang('admin::website-languages.deactivate')</button></form>
                        @endif
                        @if ($language->id !== $primary->id)
                            <form method="POST" action="{{ route('admin.settings.website-languages.primary', $language->id) }}">@csrf <button class="primary-button">@lang('admin::website-languages.make-primary')</button></form>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</x-admin::layouts>
