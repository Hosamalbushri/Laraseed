@php
    $e = array_merge([
        'particles' => true, 'orbs' => true, 'grid' => true, 'custom_cursor' => false,
        'visual_cards' => true, 'scroll_hint' => true, 'font_awesome' => true,
    ], $opts['effects'] ?? []);
    $c = array_merge([
        'bg_start' => '#0a0a2a', 'bg_mid' => '#1a1a3a', 'bg_end' => '#0f0f2a',
        'accent' => '#8b5cf6', 'accent_2' => '#6366f1', 'text' => '#ffffff',
        'text_muted' => 'rgba(255,255,255,0.7)',
        'orb_1' => 'rgba(139, 92, 246, 0.8)', 'orb_2' => 'rgba(236, 72, 153, 0.6)', 'orb_3' => 'rgba(59, 130, 246, 0.7)',
    ], $opts['colors'] ?? []);
    $badge = array_merge(['enabled' => true, 'icon' => 'fas fa-calendar-star', 'text' => ''], $opts['badge'] ?? []);
    $heading = array_merge(['line1' => '', 'highlight' => ''], $opts['heading'] ?? []);
    $typing = array_merge(['prefix' => '', 'words' => []], $opts['typing'] ?? []);
    $wordsText = old('options.typing.words_text', implode("\n", $typing['words'] ?? []));
    $primaryCta = array_merge(['label' => '', 'url' => '', 'icon' => 'fas fa-compass'], $opts['primary_cta'] ?? []);
    $secondaryCta = array_merge(['enabled' => true, 'label' => '', 'url' => '', 'icon' => 'fas fa-plus-circle'], $opts['secondary_cta'] ?? []);
    $cards = $opts['cards'] ?? [];
    while (count($cards) < 3) {
        $cards[] = ['icon' => '', 'date_line' => '', 'title' => '', 'attendees' => ''];
    }
    $scrollHint = array_merge(['text' => ''], $opts['scroll_hint'] ?? []);
    $particlesCount = (int) ($opts['particles_count'] ?? 80);
@endphp

<div class="flex flex-col gap-4">
    <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <p class="mb-1 text-base font-semibold text-gray-800 dark:text-white">
            @lang('admin::app.settings.shop-theme.edit.immersive.effects-title')
        </p>
        <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">
            @lang('admin::app.settings.shop-theme.edit.immersive.effects-help')
        </p>
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ([
                'particles' => 'particles',
                'orbs' => 'orbs',
                'grid' => 'grid',
                'custom_cursor' => 'custom-cursor',
                'visual_cards' => 'visual-cards',
                'scroll_hint' => 'scroll-hint',
                'font_awesome' => 'font-awesome',
            ] as $key => $labelKey)
                <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
                    <input type="hidden" name="options[effects][{{ $key }}]" value="0">
                    <input
                        type="checkbox"
                        name="options[effects][{{ $key }}]"
                        value="1"
                        class="rounded border-gray-300 text-brandColor focus:ring-brandColor dark:border-gray-600 dark:bg-gray-900"
                        @checked(old("options.effects.$key", $e[$key] ?? false))
                    >
                    <span>@lang('admin::app.settings.shop-theme.edit.immersive.effect.'.$labelKey)</span>
                </label>
            @endforeach
        </div>
        <div class="mt-4 max-w-xs">
            <x-admin::form.control-group.label>
                @lang('admin::app.settings.shop-theme.edit.immersive.particles-count')
            </x-admin::form.control-group.label>
            <x-admin::form.control-group.control
                type="number"
                name="options[particles_count]"
                :value="old('options.particles_count', $particlesCount)"
                rules="numeric"
            />
        </div>
    </div>

    <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
            @lang('admin::app.settings.shop-theme.edit.immersive.colors-title')
        </p>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                'bg_start' => 'bg-start', 'bg_mid' => 'bg-mid', 'bg_end' => 'bg-end',
                'accent' => 'accent', 'accent_2' => 'accent-2', 'text' => 'text',
                'text_muted' => 'text-muted', 'orb_1' => 'orb-1', 'orb_2' => 'orb-2', 'orb_3' => 'orb-3',
            ] as $field => $labelKey)
                <x-admin::form.control-group class="!mb-0">
                    <x-admin::form.control-group.label>
                        @lang('admin::app.settings.shop-theme.edit.immersive.color.'.$labelKey)
                    </x-admin::form.control-group.label>
                    <x-admin::form.control-group.control
                        type="text"
                        :name="'options[colors]['.$field.']'"
                        :value="old('options.colors.'.$field, $c[$field] ?? '')"
                    />
                </x-admin::form.control-group>
            @endforeach
        </div>
    </div>

    <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
            @lang('admin::app.settings.shop-theme.edit.immersive.badge-title')
        </p>
        <label class="mb-4 flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
            <input type="hidden" name="options[badge][enabled]" value="0">
            <input type="checkbox" name="options[badge][enabled]" value="1" class="rounded border-gray-300 text-brandColor dark:border-gray-600 dark:bg-gray-900" @checked(old('options.badge.enabled', $badge['enabled'] ?? true))>
            @lang('admin::app.settings.shop-theme.edit.immersive.badge-enabled')
        </label>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-admin::form.control-group class="!mb-0">
                <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.immersive.badge-icon')</x-admin::form.control-group.label>
                <x-admin::form.control-group.control type="text" name="options[badge][icon]" :value="old('options.badge.icon', $badge['icon'] ?? '')" />
            </x-admin::form.control-group>
            <x-admin::form.control-group class="!mb-0">
                <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.immersive.badge-text')</x-admin::form.control-group.label>
                <x-admin::form.control-group.control type="text" name="options[badge][text]" :value="old('options.badge.text', $badge['text'] ?? '')" />
            </x-admin::form.control-group>
        </div>
    </div>

    <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
            @lang('admin::app.settings.shop-theme.edit.immersive.heading-title')
        </p>
        <div class="grid gap-4">
            <x-admin::form.control-group class="!mb-0">
                <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.immersive.heading-line1')</x-admin::form.control-group.label>
                <x-admin::form.control-group.control type="textarea" name="options[heading][line1]" :value="old('options.heading.line1', $heading['line1'] ?? '')" />
            </x-admin::form.control-group>
            <x-admin::form.control-group class="!mb-0">
                <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.immersive.heading-highlight')</x-admin::form.control-group.label>
                <x-admin::form.control-group.control type="text" name="options[heading][highlight]" :value="old('options.heading.highlight', $heading['highlight'] ?? '')" />
            </x-admin::form.control-group>
        </div>
    </div>

    <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
            @lang('admin::app.settings.shop-theme.edit.immersive.typing-title')
        </p>
        <x-admin::form.control-group class="!mb-4">
            <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.immersive.typing-prefix')</x-admin::form.control-group.label>
            <x-admin::form.control-group.control type="text" name="options[typing][prefix]" :value="old('options.typing.prefix', $typing['prefix'] ?? '')" />
        </x-admin::form.control-group>
        <x-admin::form.control-group class="!mb-0">
            <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.immersive.typing-words')</x-admin::form.control-group.label>
            <textarea
                name="options[typing][words_text]"
                rows="5"
                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
            >{{ $wordsText }}</textarea>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">@lang('admin::app.settings.shop-theme.edit.immersive.typing-words-help')</p>
        </x-admin::form.control-group>
    </div>

    <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
            @lang('admin::app.settings.shop-theme.edit.immersive.description-title')
        </p>
        <textarea
            name="options[description]"
            rows="4"
            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-800 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
        >{{ old('options.description', $opts['description'] ?? '') }}</textarea>
    </div>

    <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
            @lang('admin::app.settings.shop-theme.edit.immersive.cta-primary')
        </p>
        <div class="grid gap-4 sm:grid-cols-3">
            <x-admin::form.control-group class="!mb-0">
                <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.immersive.cta-label')</x-admin::form.control-group.label>
                <x-admin::form.control-group.control type="text" name="options[primary_cta][label]" :value="old('options.primary_cta.label', $primaryCta['label'] ?? '')" />
            </x-admin::form.control-group>
            <x-admin::form.control-group class="!mb-0">
                <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.immersive.cta-url')</x-admin::form.control-group.label>
                <x-admin::form.control-group.control type="text" name="options[primary_cta][url]" :value="old('options.primary_cta.url', $primaryCta['url'] ?? '')" />
            </x-admin::form.control-group>
            <x-admin::form.control-group class="!mb-0">
                <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.immersive.cta-icon')</x-admin::form.control-group.label>
                <x-admin::form.control-group.control type="text" name="options[primary_cta][icon]" :value="old('options.primary_cta.icon', $primaryCta['icon'] ?? '')" />
            </x-admin::form.control-group>
        </div>
    </div>

    <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
            @lang('admin::app.settings.shop-theme.edit.immersive.cta-secondary')
        </p>
        <label class="mb-4 flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
            <input type="hidden" name="options[secondary_cta][enabled]" value="0">
            <input type="checkbox" name="options[secondary_cta][enabled]" value="1" class="rounded border-gray-300 text-brandColor dark:border-gray-600 dark:bg-gray-900" @checked(old('options.secondary_cta.enabled', $secondaryCta['enabled'] ?? true))>
            @lang('admin::app.settings.shop-theme.edit.immersive.secondary-enabled')
        </label>
        <div class="grid gap-4 sm:grid-cols-3">
            <x-admin::form.control-group class="!mb-0">
                <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.immersive.cta-label')</x-admin::form.control-group.label>
                <x-admin::form.control-group.control type="text" name="options[secondary_cta][label]" :value="old('options.secondary_cta.label', $secondaryCta['label'] ?? '')" />
            </x-admin::form.control-group>
            <x-admin::form.control-group class="!mb-0">
                <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.immersive.cta-url')</x-admin::form.control-group.label>
                <x-admin::form.control-group.control type="text" name="options[secondary_cta][url]" :value="old('options.secondary_cta.url', $secondaryCta['url'] ?? '')" />
            </x-admin::form.control-group>
            <x-admin::form.control-group class="!mb-0">
                <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.immersive.cta-icon')</x-admin::form.control-group.label>
                <x-admin::form.control-group.control type="text" name="options[secondary_cta][icon]" :value="old('options.secondary_cta.icon', $secondaryCta['icon'] ?? '')" />
            </x-admin::form.control-group>
        </div>
    </div>

    <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
            @lang('admin::app.settings.shop-theme.edit.immersive.cards-title')
        </p>
        @foreach ([0, 1, 2] as $i)
            <div class="mb-6 rounded-md border border-gray-100 p-3 dark:border-gray-800">
                <p class="mb-3 text-sm font-medium text-gray-600 dark:text-gray-300">@lang('admin::app.settings.shop-theme.edit.immersive.card-n', ['n' => $i + 1])</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.immersive.card-icon')</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="text" :name="'options[cards]['.$i.'][icon]'" :value="old('options.cards.'.$i.'.icon', $cards[$i]['icon'] ?? '')" />
                    </x-admin::form.control-group>
                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.immersive.card-date')</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="text" :name="'options[cards]['.$i.'][date_line]'" :value="old('options.cards.'.$i.'.date_line', $cards[$i]['date_line'] ?? '')" />
                    </x-admin::form.control-group>
                    <x-admin::form.control-group class="!mb-0 sm:col-span-2">
                        <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.immersive.card-title')</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="text" :name="'options[cards]['.$i.'][title]'" :value="old('options.cards.'.$i.'.title', $cards[$i]['title'] ?? '')" />
                    </x-admin::form.control-group>
                    <x-admin::form.control-group class="!mb-0 sm:col-span-2">
                        <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.immersive.card-attendees')</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="text" :name="'options[cards]['.$i.'][attendees]'" :value="old('options.cards.'.$i.'.attendees', $cards[$i]['attendees'] ?? '')" />
                    </x-admin::form.control-group>
                </div>
            </div>
        @endforeach
    </div>

    <div class="box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
            @lang('admin::app.settings.shop-theme.edit.immersive.scroll-title')
        </p>
        <x-admin::form.control-group class="!mb-0">
            <x-admin::form.control-group.label>@lang('admin::app.settings.shop-theme.edit.immersive.scroll-text')</x-admin::form.control-group.label>
            <x-admin::form.control-group.control type="text" name="options[scroll_hint][text]" :value="old('options.scroll_hint.text', $scrollHint['text'] ?? '')" />
        </x-admin::form.control-group>
    </div>
</div>
